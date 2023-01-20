<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Friend;
use Illuminate\Http\Request;
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
            new InviteFriend($user, auth()->user()->name . ' has sent you a friend request.')
        );

        return response()->noContent();

    }

    public function accept(Request $request, Friend $friend): \Illuminate\Http\Response
    {
        $this->authorize('acceptFriend', $friend);
        
        auth()->user()->notifications->where('type', InviteFriend::class)->markAsRead();

        $friend->update([
            'friends' => 1
        ]);

        $friend->user->notify(
            new AcceptFriendInvite($friend->user, auth()->user()->name . ' has accepted your friend request.')
        );

        return response()->noContent();
    }

    public function reject(Request $request, Friend $friend): \Illuminate\Http\Response
    {
        $this->authorize('rejectFriend', $user);
        
        auth()->user()->notifications->where('type', InviteFriend::class)->markAsRead();

        Friend::where([
            ['user_id', '=', $user->id],
            ['friend_id', '=', auth()->user()->id],
            ['friends', '=', 0]
        ])->delete();

        $user->notify(
            new RejectFriendInvite($user, auth()->user()->name . ' has rejected your friend request.')
        );

        return response()->noContent();
    }

    public function remove(Request $request, Friend $friend): \Illuminate\Http\Response
    {

        $this->authorize('removeFriend', $friend);
         
        Friend::where([
            ['user_id', '=', $user->id],
            ['friend_id', '=', auth()->user()->id],
            ['friends', '=', 1]
        ])->orWhere([
            ['user_id', '=', auth()->user()->id],
            ['friend_id', '=', $user->id],
            ['friends', '=', 1]
        ])->delete();

        return response()->noContent();
    }

}
