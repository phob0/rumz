<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Friend;
use App\Models\Notification;
use Illuminate\Http\Request;
use Spatie\Searchable\Search;
use App\Notifications\InviteFriend;
use App\Notifications\AcceptFriendInvite;
use App\Notifications\RejectFriendInvite;
use Illuminate\Http\Resources\Json\JsonResource;

class FriendController extends Controller
{

    public function lookup(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return JsonResource::collection(auth()->user()->friends);
    }

    public function invite(Request $request, User $user): \Illuminate\Http\Response
    {
        if (
            Friend::where([
                ['user_id', '=', $user->id],
                ['friend_id', '=', auth()->user()->id]
            ])->orWhere([
                ['user_id', '=', auth()->user()->id],
                ['friend_id', '=', $user->id]
            ])->exists()
        ) {
            abort(403);
        }

        Friend::create([
            'user_id' => auth()->user()->id,
            'friend_id' => $user->id,
            'friends' => 0
        ]);

        $user->notify(
            new InviteFriend($user, auth()->user(), auth()->user()->name . ' has sent you a friend request.')
        );

        return response()->noContent();

    }

    public function accept(Request $request, Friend $friend): \Illuminate\Http\Response
    {
        $this->authorize('acceptFriend', $friend);

        $notification = auth()->user()->notifications->where('type', InviteFriend::class)->first();

        if (!is_null($notification)) {
            Notification::find($notification->id)->forceDelete();
        }

        $friend->update([
            'friends' => 1
        ]);

        $friend->user->notify(
            new AcceptFriendInvite($friend->user, auth()->user(), auth()->user()->name . ' has accepted your friend request.')
        );

        return response()->noContent();
    }

    public function reject(Request $request, Friend $friend): \Illuminate\Http\Response
    {
        $this->authorize('rejectFriend', $friend);
        
        $notification = auth()->user()->notifications->where('type', InviteFriend::class)->first();

        if (!is_null($notification)) {
            Notification::find($notification->id)->forceDelete();
        }

        $friend->user->notify(
            new RejectFriendInvite($friend->user, auth()->user(), auth()->user()->name . ' has rejected your friend request.')
        );

        $friend->delete();

        return response()->noContent();
    }

    public function remove(Request $request, Friend $friend): \Illuminate\Http\Response
    {

        $this->authorize('removeFriend', $friend);
         
        $friend->delete();

        return response()->noContent();
    }

    public function search(Request $request): \Spatie\Searchable\SearchResultCollection|\Illuminate\Support\Collection
    {
        $authID = auth()->user()->id;

        if (is_null($request->q)) {
            return JsonResource::collection(auth()->user()->friends)->collection;
        } else {
            return (new \Spatie\Searchable\Search())
            ->registerModel(\App\Models\User::class, function(\Spatie\Searchable\ModelSearchAspect $modelSearchAspect) use($authID) {
                $modelSearchAspect
                   ->addExactSearchableAttribute('name')
                   ->addSearchableAttribute('email')
                   ->addExactSearchableAttribute('phone')
                   ->whereHas('isFriends', fn($query) => $query->where('user_id', $authID))
                   ->orWhereHas('hasFriends', fn($query) => $query->where('friend_id', $authID));
            })->search($request->q)->pluck('searchable');
        }
        
    }

}
