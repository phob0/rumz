<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRumRequest;
use App\Http\Requests\UpdateRumRequest;
use App\Models\Image;
use App\Models\Rum;
use App\Models\RumHashtag;
use App\Models\User;
use App\Notifications\AcceptInvite;
use App\Notifications\BanMember;
use App\Notifications\BanUnbanMember;
use App\Notifications\InviteMember;
use App\Notifications\RemoveMember;
use App\Notifications\RumApprovalSubscriber;
use App\Notifications\RumRejectionSubscriber;
use App\Notifications\RumSubscriptionApproval;
use App\Notifications\RumSubscriptionPaymentInfo;
use App\Notifications\UnbanMember;
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

    /**
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
        $data['image'] = $path;

        $rum = Rum::create(
            Arr::add(
                Arr::except($data, ['hashtags', 'url']),
                'user_id',
                auth()->user()->id
            )
        );

        Image::create([
            'url' => $data['image'],
            'imageable_id' => $rum->id,
            'imageable_type' => Rum::class,
        ]);

        if(!empty($hashtags)) {
            collect($hashtags)->each(function($hashtag) use($rum) {
                $rum->hashtags()->create([
                    'hashtag' => $hashtag
                ]);
            });
        }

        return JsonResource::make($rum);
    }

    public function edit(Rum $rum): JsonResource
    {
        $this->authorize('edit', $rum);

        return JsonResource::make($rum);
    }

    public function update(UpdateRumRequest $request, Rum $rum): \Illuminate\Http\Response
    {
        $this->authorize('update', $rum);

        $hashtags = array_filter($request->validated()['hashtags'], 'strlen'); null;

        $data = $request->validated();

        if (Storage::disk('local')->exists('public/images/temp/'.$request->image)) {
            Storage::disk('local')->move('public/images/temp/'.$request->image, 'public/images/rums/'.$request->image);
        }

        $rum->update(
            Arr::except($data, ['hashtags', 'image'])
        );

        Image::create([
            'url' => $data['image'],
            'imageable_id' => $rum->id,
            'imageable_type' => Rum::class,
        ]);

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
        return JsonResource::collection(RumHashtag::where('hashtag', 'like', $request->q.'%')->get());
    }

    public function join(Request $request, Rum $rum, $type = 'free'): \Illuminate\Http\Response
    {
        $this->authorize('join', [$rum, $type]);

        if($type === 'paid') {
            DB::transaction(function() use($rum, $request) {
                $subscription = $rum->subscriptions()->updateOrCreate([
                    'user_id' => auth()->user()->id,
                ], [
                    'is_paid' => 1,
                    'expire_at' => Carbon::now()->addMonth(),
                    'amount' => $request->amount
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

        $rum->joined()->where('user_id', $user->id)->first()->update([
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
        $rum->joined()->where('user_id', $user->id)->first()->delete();

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
    // TODO: Write test to check response
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
    // TODO: Write test to check response
    public function acceptInvite(Request $request, Rum $rum)
    {
        $this->authorize('acceptInvite', $rum);

        $rum->joined()->where([
            ['user_id', '=', auth()->user()->id],
            ['rum_id', '=', $rum->id],
            ['granted', '=', 0],
        ])->first()->update([
            'granted' => 0
        ]);

        $rum->master->notify(
            new AcceptInvite(auth()->user()->id . 'has accepted your invite.')
        );

        return response()->noContent();
    }
    // TODO: Write test to check response
    public function banUnban(Request $request, $action,Rum $rum, User $user): \Illuminate\Http\Response
    {
        $this->authorize('banOrUnbanMembers', [$rum, $user]);

        $ban = $action === 'ban';

        if ($rum->type !== Rum::TYPE_PAID) {
            $rum->joined()->where('user_id', $user->id)->first()->update([
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
    // TODO: Write test to check response
    public function removeMember(Request $request, Rum $rum, User $user): \Illuminate\Http\Response
    {
        $this->authorize('removeMembers', $rum);

        $rum->joined()->where('user_id', $user->id)->first()->delete();

        $user->notify(
            new RemoveMember($rum, 'You have been banned from this rum.')
        );

        return response()->noContent();
    }
    // TODO: Write test to check response
    public function reportRum(Request $request, Rum $rum): \Illuminate\Http\Response
    {
        $this->authorize('membersList', $rum);

        User::superadmins()->each(function($admin) use($rum){
            $admin->notify(
                $rum,
                'A user has reported this rum.'
            );
        });

        return response()->noContent();
    }

    public function image(Request $request): \Illuminate\Http\JsonResponse
    {
        $file = $request->file('image');
        $path = $file->store('public/images/temp');

        return response()->json([
            'path' => $path,
            'file_name' => $file->hashName()
        ]);
    }

    public function search(Request $request): \Spatie\Searchable\SearchResultCollection
    {
        return (new Search())
            ->registerModel(Rum::class, ['title', 'description'])
            ->search($request->q);
    }

    /*
     * TODO
     *  Common users can see Rum info.
        add search method
        split into explore|collection:
        Explore is a tailored experience providing the user with a feed of rumz in accordance with their interest, experience and localization.
        Collection is a mix made from rumz joined by user and rumz created by user, will appear after user create or join first rum.
     */

}
