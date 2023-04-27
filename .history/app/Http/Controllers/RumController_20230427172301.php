<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRumRequest;
use App\Http\Requests\UpdateRumRequest;
use App\Http\Resources\RumPostResource;
use App\Interfaces\NotificationTypes;
use App\Models\Image;
use App\Models\Notification;
use App\Models\Rum;
use App\Models\RumHashtag;
use App\Models\RumPost;
use App\Models\User;
use App\Models\UserRum;
use App\Notifications\AcceptAdminInvite;
use App\Notifications\AcceptInvite;
use App\Notifications\BanUnbanMember;
use App\Notifications\InviteAdminMember;
use App\Notifications\InviteMember;
use App\Notifications\NewMember;
use App\Notifications\RejectAdminInvite;
use App\Notifications\RemoveMember;
use App\Notifications\RumApprovalSubscriber;
use App\Notifications\RumRejectionSubscriber;
use App\Notifications\RumReport;
use App\Notifications\RumSubscriptionApproval;
use App\Notifications\RumSubscriptionPaymentInfo;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Searchable\Search;
use Symfony\Component\HttpFoundation\Response;

class RumController extends Controller implements NotificationTypes
{

    // TODO: uprade returns with specific resources
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return JsonResource::collection(Rum::where('type', '!=', 'confidential')->get());
    }
    // TODO: DEPRECATE explore, myRums and currentRums
    public function explore(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return JsonResource::collection(Rum::with('posts')->where('type', '!=', 'confidential')->get());
    }

    public function myRums(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return JsonResource::collection(auth()->user()->rums()->with('posts')->get());
    }

    public function currentRums(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        // TODO: adaug alea din my-rums
        return JsonResource::collection(
            auth()->user()->joinedRums()->with('posts')->get()->concat(
                auth()->user()->subscribedRums()->with('posts')->get()
            )
        );
    }

    public function feedRums(Request $request, $type): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        if ($type === 'current') {
            // TODO: adaug alea din my-rums
            return JsonResource::collection(
                auth()->user()->joinedRums()->with('posts')->get()->concat(
                    auth()->user()->subscribedRums()->with('posts')->get()
                )
            );
        } else if($type === 'my') {
            return JsonResource::collection(auth()->user()->rums()->with('posts')->get());
        } else {
            return JsonResource::collection(Rum::with('posts')->where('type', '!=', 'confidential')->get());
        }
    }

    /**
     * @deprecated
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function view(Rum $rum): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('view', $rum);

        return RumPostResource::collection($rum->posts()->orderBy('created_at', 'desc')->get());
    }

    public function store(StoreRumRequest $request): JsonResource
    {

        $hashtags = array_filter($request->validated()['hashtags'], 'strlen');
        $path = !is_null($request->file('image')) ? $request->file('image')->store('public/images/rums') : null;

        $data = $request->validated();

        $data['image'] = link_image_path($path);

        $rum = Rum::create(
            Arr::add(
                Arr::except($data, ['hashtags', 'url']),
                'user_id',
                auth()->user()->id
            )
        );

        if (is_null($rum->image) && !is_null($data['image'])) {
            $rum->image()->create([
                'url' => $data['image'],
                'imageable_id' => $rum->id,
                'imageable_type' => Rum::class,
            ]);
        }

        if(!empty($hashtags)) {
            collect($hashtags)->each(function($hashtag) use($rum) {
                $rum->hashtags()->create([
                    'hashtag' => $hashtag
                ]);
            });
        }

        return JsonResource::make($rum->load([
            'hashtags',
            'users',
            'subscribed',
            'image'
        ]));
    }

    public function edit(Rum $rum): JsonResource
    {
        $this->authorize('edit', $rum);

        return JsonResource::make($rum);
    }

    public function update(UpdateRumRequest $request, Rum $rum): \Illuminate\Http\Response
    {
        /*
         * TODO add invite admins
         * */
        $this->authorize('update', $rum);

        $hashtags = array_filter($request->validated()['hashtags'], 'strlen'); null;

        $data = $request->validated();

        $rum->update(
            Arr::except($data, ['hashtags', 'image'])
        );

        if (
            (!is_null($rum->image) && !is_null($data['image'])) &&
            get_image_name($rum->image->url) !== $data['image'])
        {
            if (Storage::disk('local')->exists('public/images/temp/'.$request->image)) {
                Storage::disk('local')->move('public/images/temp/'.$request->image, 'public/images/rums/'.$request->image);
            }

            $this->removeImage(public_image_path($rum->image->url));

            $rum->image()->update([
                'url' => 'storage/images/rums/' . $data['image'],
                'imageable_id' => $rum->id,
                'imageable_type' => Rum::class,
            ]);
        } else if (is_null($data['image']) || $data['image'] == "")
        {
            $this->removeImage($rum->image->url);

            $rum->image()->delete();
        }

        $rum->hashtags()->delete();

        if(!empty($hashtags)) {
            collect($hashtags)->each(function($hashtag) use($rum) {
                $rum->hashtags()->create([
                    'hashtag' => $hashtag
                ]);
            });
        }

        return response()->noContent();
    }

    public function delete(Rum $rum): \Illuminate\Http\Response
    {
        $this->authorize('delete', $rum);

        $this->removeImage($rum->image->url);

        $rum->delete();

        return response()->noContent();
    }

    public function hashtagSuggestions(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return JsonResource::collection(RumHashtag::where('hashtag', 'like', $request->q.'%')->get('hashtag'));
    }

    public function paymentSheet(Request $request, Rum $rum): \Illuminate\Http\JsonResponse
    {
        \Log::info('request amount for stripe is'.$request->amount);

        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

        // Use an existing Customer ID if this is a returning customer.
        $customer = $stripe->customers->create();
        $ephemeralKey = $stripe->ephemeralKeys->create([
          'customer' => $customer->id,
        ], [
          'stripe_version' => '2022-08-01',
        ]);

        $paymentIntent = $stripe->paymentIntents->create([
          'amount' => $this->parseAmount($request->amount),
          'currency' => 'usd',
          'customer' => $customer->id,
          'automatic_payment_methods' => [
            'enabled' => 'true',
          ],
        ]);

        return response()->json(
          [
            'paymentIntentID' => $paymentIntent->id,
            'paymentIntentSecret' => $paymentIntent->client_secret,
            'ephemeralKey' => $ephemeralKey->secret,
            'customer' => $customer->id
          ]
        );


    }

    public function join(Request $request, Rum $rum, $type = 'free'): \Illuminate\Http\Response
    {
        $this->authorize('join', [$rum, $type]);

        if($type === 'paid') {
            // TODO: add rum_id to cashier subscriptions table
            // TODO: remove quantity column

            // TODO: remove quantity from subscription_items
            // TODO: add default value to stripe_product column

            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

            $connected_account = $rum->master->stripe_id;

            $paymentIntent = $stripe->paymentIntents->retrieve(
                $request->paymentIntent,
                []
            );

            $lastCharge = end($paymentIntent->charges->data);

            $final_amount = $this->subtractAdminTax($lastCharge->amount);

            $transfer = $stripe->transfers->create([
                "amount" => $final_amount,
                "currency" => "usd",
                "source_transaction" => $lastCharge->id,
                "destination" => $connected_account,
            ]);

            DB::transaction(function() use($rum, $request, $transfer) {
                $subscription = $rum->subscriptions()->updateOrCreate([
                    'user_id' => auth()->user()->id,
                ], [
                    'is_paid' => 1,
                    'expire_at' => Carbon::now()->addMonth(),
                    'transfer_id' => $transfer->id,
                    'amount' => $request->amount,
                    'owner_amount' => $this->subtractAdminTax($request->amount),
                    'profit' => ($request->amount - $this->subtractAdminTax($request->amount))
                ]);

                $subscription->history_payments()->create([
                    'amount' => $request->amount
                ]);
            });

            $rum->master->notify(new RumSubscriptionPaymentInfo($rum, auth()->user()->name . ' payed $'.$request->amount.' membership to join your rum.'));
        } else {
            $rum->joined()->create([
                'user_id' => auth()->user()->id,
                'granted' => ($type === 'free')
            ]);

            if ($type !== 'free') {
                $rum->master->notify(new RumSubscriptionApproval($rum, auth()->user(),auth()->user()->name . ' is waiting your approval.'));
            }
        }

        return response()->noContent();
    }

    public function grant(Request $request, Rum $rum, User $user): \Illuminate\Http\Response
    {
        $this->authorize('grant', [$rum, $user]);

        $rum->join_requests()
            ->where('rum_id', $rum->id)
            ->where('user_id', $user->id)
            ->first()->update([
                'granted' => true
            ]);

        $notification = auth()->user()->unreadNotifications->filter(function($item) use($rum) {
            return $item->data['rum']['id'] === $rum->id;
        })->first();

        Notification::find($notification->id)->forceDelete();

        $user->notify(
            new RumApprovalSubscriber(
                $rum->fresh(),
                $rum->joined()->where('rum_id', $rum->id)
                    ->where('user_id', $user->id)
                    ->first(),
                'Your request to join has been approved'
            )
        );

        return response()->noContent();
    }

    public function reject(Request $request, Rum $rum, User $user): \Illuminate\Http\Response
    {
        $rum->join_requests()
            ->where('user_id', $user->id)
            ->where('rum_id', $rum->id)
            ->first()->delete();

        $notification = auth()->user()->unreadNotifications->filter(function($item) use($rum) {
            return $item->data['rum']['id'] === $rum->id;
        })->first();

        Notification::find($notification->id)->forceDelete();

        $user->notify(
            new RumRejectionSubscriber(
                $rum->fresh(),
                'Your request to join has been rejected'
            )
        );

        return response()->noContent();
    }

    public function adminsList(Request $request, Rum $rum): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('adminsList', $rum);

        return JsonResource::collection($rum->admins);
    }

    public function membersList(Request $request, Rum $rum): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('membersList', $rum);

        return JsonResource::collection(
            $rum->users
                ->concat($rum->subscribed)
                ->concat(auth()->user()->friends)
            );
    }

    public function inviteMember(Request $request, Rum $rum, User $user): \Illuminate\Http\Response
    {
        $this->authorize('inviteMember', [$rum, $user]);

        $rum->joined()->create([
            'user_id' => $user->id,
            'granted' => 0
        ]);

        $user->notify(
            new InviteMember($rum, auth()->user()->name . ' has invited you to join this rum. Please submit a response.')
        );

        return response()->noContent();
    }

    public function inviteAdminMembers(Request $request, Rum $rum): \Illuminate\Http\Response
    {
        $this->authorize('inviteAdminMembers', [$rum, $request->members]);

        $inviteMessage = auth()->user()->name . ' has invited you to become an admin member to a rum. Login in to your account or sign up to ' . env('APP_NAME') . 'to accept the invitation.';

        $currentUsers = collect(User::without(['image'])->get('phone')->toArray())
            ->flatten();

        $existingUsers = collect([]);

        collect($request->members)
            ->each(fn($number) => !in_array($number, $currentUsers->toArray()) ? $this->sendSMS($number, $inviteMessage) : $existingUsers->push($number));


        $existingUsers->each(function($member) use($rum){
            $user = User::where('phone', $member)->first();

            if (!is_null($user)) {
                $rum->join_admin_requests()->create([
                    'user_id' => $user->id,
                    'granted' => 0
                ]);

                $user->notify(
                    new InviteAdminMember($rum, auth()->user()->name . ' has invited you to be an admin of this rum. Please submit a response.')
                );
            }

        });

        return response()->noContent();
    }

    public function acceptInviteMember(Request $request, Rum $rum): \Illuminate\Http\Response
    {
        $this->authorize('acceptInvite', $rum);

        auth()->user()->notifications->where('type', InviteMember::class)->markAsRead();

        $rum->join_requests()->where([
            ['user_id', '=', auth()->user()->id],
            ['rum_id', '=', $rum->id],
        ])->first()->update([
            'granted' => 1
        ]);

        $rum->master->notify(
            new AcceptInvite($rum, auth()->user()->id . ' has accepted your invite.')
        );

        $rum->users->concat($rum->subscribed)->each(function($user) {
            if ($user->id !== auth()->user()->id) {
                $user->notify(
                    new NewMember(auth()->user()->name . ' has joined the rum.')
                );
            }
        });

        return response()->noContent();
    }

    public function acceptAdminInviteMember(Request $request, Rum $rum): \Illuminate\Http\Response
    {
        $this->authorize('acceptAdminInvite', $rum);

        $rum->join_admin_requests()->where('user_id', auth()->user()->id)->update([
            'user_id' => auth()->user()->id,
            'granted' => 1
        ]);

        $notification = auth()->user()->notifications->filter(function($item) use($rum) {
            return $item->data['rum']['id'] === $rum->id && $item->data['notification_type'] === self::ADMIN_ROOM_INVITATION;
        })->first();

        Notification::find($notification->id)->forceDelete();

        $rum->master->notify(
            new AcceptAdminInvite(
                $rum,
                auth()->user()->name . ' has accepted your invite.'
            )
        );

        return response()->noContent();
    }

    public function rejectAdminInviteMember(Request $request, Rum $rum, User $user): \Illuminate\Http\Response
    {
        $this->authorize('rejectAdminInvite', $rum);

        $rum->join_admin_requests()
            ->where('user_id', auth()->user()->id)
            ->delete();

        $notification = auth()->user()->notifications->filter(function($item) use($rum) {
            return $item->data['rum']['id'] === $rum->id && $item->data['notification_type'] === self::ADMIN_ROOM_INVITATION;
        })->first();

        Notification::find($notification->id)->forceDelete();

        $rum->master->notify(
            new RejectAdminInvite(
                $rum,
                auth()->user()->name . ' has rejected your invite.'
            )
        );

        return response()->noContent();
    }

    public function banUnbanMember(Request $request, $action,Rum $rum, User $user): \Illuminate\Http\Response
    {
        $this->authorize('banOrUnbanMembers', [$rum, $user, $action]);

        $ban = $action !== 'ban';

        if ($rum->type !== Rum::TYPE_PAID) {
            UserRum::where([
                ['user_id', $user->id],
                ['rum_id', $rum->id],
            ])->first()->update([
                'granted' => $ban
            ]);
        } else {
            $rum->subscriptions()->where('user_id', $user->id)->first()->update([
                'granted' => $ban
            ]);
        }

        $message = $ban ?
            'You have been banned from this rum.' :
            'Your ban from this rum has been removed.';

        $user->notify(
            new BanUnbanMember($rum, $message, $ban)
        );

        return response()->noContent();
    }

    public function removeMember(Request $request, Rum $rum, User $user): \Illuminate\Http\Response
    {
        $this->authorize('removeMembers', [$rum, $user]);

        $rum->joined()->where('user_id', $user->id)->first()->delete();

        $user->notify(
            new RemoveMember($rum, 'You have been removed from this rum.')
        );

        return response()->noContent();
    }

    public function reportRum(Request $request, Rum $rum): \Illuminate\Http\Response
    {
        $this->authorize('membersList', $rum);

        User::superadmins()->each(function($admin) use($rum){
            $admin->notify(
                new RumReport(
                    $rum,
                    'A user has reported this rum.'
                )
            );
        });

        return response()->noContent();
    }

    public function search(Request $request, $type): \Spatie\Searchable\SearchResultCollection
    {
        if ($type === 'current') {
            return (new Search())
                // ->registerModel(Rum::class, ['title', 'description'])
                ->registerModel(Rum::class, function($modelSearchAspect) {
                    $modelSearchAspect
                       ->addSearchableAttribute('title') // return results for partial matches on usernames
                       ->addExactSearchableAttribute('description') // only return results that exactly match the e-mail address
                       ->where('type', '!=', 'confidential');
                })
                ->search($request->q);

        } else {}

    }

    private function parseAmount($amount): float|int
    {
        return $amount * 100;
    }

    private function subtractAdminTax($amount): float
    {
        return $amount - ($amount * 0.1);
    }

    /*
     * TODO
     *  multiple admins on rum, invitation and notification feature
     *  Common users can see Rum info.
        add search method
        split into explore|collection:
        Explore is a tailored experience providing the user with a feed of rumz in accordance with their interest, experience and localization.
        Collection is a mix made from rumz joined by user and rumz created by user, will appear after user create or join first rum.
     */

}
