<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRumRequest;
use App\Http\Requests\UpdateRumRequest;
use App\Models\Image;
use App\Models\Rum;
use App\Models\RumHashtag;
use App\Models\User;
use App\Models\UserRum;
use App\Notifications\AcceptInvite;
use App\Notifications\BanUnbanMember;
use App\Notifications\InviteMember;
use App\Notifications\NewMember;
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

class RumController extends Controller
{

    // TODO: uprade returns with specific resources
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return JsonResource::collection(Rum::where('type', '!=', 'confidential')->get());
    }

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
        return JsonResource::collection(
            auth()->user()->joinedRums()->with('posts')->get()->concat(
                auth()->user()->subscribedRums()->with('posts')->get()
            )
        );
    }

    /**
     * @deprecated
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function view(Rum $rum): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('view', $rum);
        return JsonResource::collection($rum->posts);
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

        $rum->delete();

        return response()->noContent();
    }

    public function hashtagSuggestions(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return JsonResource::collection(RumHashtag::where('hashtag', 'like', $request->q.'%')->get('hashtag'));
    }

    public function join(Request $request, Rum $rum, $type = 'free'): \Illuminate\Http\Response
    {
        $this->authorize('join', [$rum, $type]);

        if($type === 'paid') {
            // add rum_id to cashier subscriptions table
            // remove quantity column

            // remove quantity from subscription_items
            // add default value to stripe_product column

            $parsedAmount = $this->parseAmount($request->amount);

            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

            $charge =  $stripe->charges->create([
                "amount" => $parsedAmount,
                "currency" => "usd",
                //        "source" => "tok_visa",
                "source" => "acct_1LTnIOPJhHLfy5Xm",
                //        for simple card charge
                //        "transfer_data" => [
                //            "amount" => 877,
                //            "destination" => "acct_1LTe3uPLLPTwYFpQ",
                //        ],
            ]);

            $transfer = $stripe->transfers->create([
                "amount" => $this->subtractAdminTax($parsedAmount),
                "currency" => "usd",
                "source_transaction" => $charge->id,
                "destination" => "acct_1LTe3uPLLPTwYFpQ",
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
        if(!isset($request->granted)) {
            throw new HttpResponseException(
                response()->json(['error' => 'Granted value is missing.'], Response::HTTP_UNPROCESSABLE_ENTITY)
            );
        }

        $this->authorize('grant', [$rum, $user]);

        $rum->join_requests()->where('user_id', $user->id)->first()->update([
            'granted' => $request->granted
        ]);

        auth()->user()->notifications->filter(function($item) use($rum) {
            return $item->data['rum']['id'] === $rum->id;
        })->markAsRead();

        $user->notify(new RumApprovalSubscriber($rum, 'Your request to join has been approved'));

        return response()->noContent();
    }

    public function reject(Request $request, Rum $rum, User $user): \Illuminate\Http\Response
    {
        $rum->join_requests()->where('user_id', $user->id)->first()->delete();
        // TODO: add follow-up to interactive notifications
        auth()->user()->unreadNotifications->filter(function($item) use($rum) {
            return $item->data['rum']['id'] === $rum->id;
        })->markAsRead();

        $user->notify(new RumRejectionSubscriber($rum, 'Your request to join has been rejected'));

        return response()->noContent();
    }

    public function membersList(Request $request, Rum $rum): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('membersList', $rum);

        return JsonResource::collection($rum->users()->concat($rum->subscribed));
    }

    public function inviteMember(Request $request, Rum $rum, User $user): \Illuminate\Http\Response
    {
        $this->authorize('inviteMember', [$rum, $user]);

        $rum->joined()->create([
            'user_id' => $user->id,
            'granted' => 0
        ]);

        $user->notify(
            new InviteMember($rum, auth()->user()->name . 'has invited you to join this rum. Please submit a response.')
        );

        return response()->noContent();
    }

    public function inviteAdminMember(Request $request, Rum $rum, User $user): \Illuminate\Http\Response
    {
        $this->authorize('inviteAdminMember', [$rum, $user]);

        $rum->joined_admins()->create([
            'user_id' => $user->id,
            'granted' => 0
        ]);

        $user->notify(
            new InviteAdminMember($rum, auth()->user()->name . 'has invited you to be an admin of this rum. Please submit a response.')
        );

        return response()->noContent();
    }

    public function acceptInviteMember(Request $request, Rum $rum): \Illuminate\Http\Response
    {
        $this->authorize('acceptInvite', $rum);

        auth()->user()->unreadNotifications->where('type', InviteMember::class)->markAsRead();

        $rum->join_requests()->where([
            ['user_id', '=', auth()->user()->id],
            ['rum_id', '=', $rum->id],
        ])->first()->update([
            'granted' => 1
        ]);

        $rum->master->notify(
            new AcceptInvite(auth()->user()->id . 'has accepted your invite.')
        );

        $rum->users->concat($rum->subscribed)->each(function($user) {
            if ($user->id !== auth()->user()->id) {
                $user->notify(
                    new NewMember(auth()->user()->name . 'has joined the rum.')
                );
            }
        });

        return response()->noContent();
    }

    public function acceptAdminInviteMember(Request $request, Rum $rum)
    {
        $this->authorize('acceptAdminInvite', $rum);

        dd(true);
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
            new BanUnbanMember($rum, $message)
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

    public function search(Request $request): \Spatie\Searchable\SearchResultCollection
    {
        return (new Search())
            ->registerModel(Rum::class, ['title', 'description'])
            ->search($request->q);
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
