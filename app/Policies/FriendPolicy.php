<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Friend;
use Illuminate\Auth\Access\HandlesAuthorization;

class FriendPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function acceptFriend(User $user, Friend $friend)
    {
        return !$friend->friends && $friend->user_id === $user->id;
    }


    public function rejectFriend(User $user, Friend $friend)
    {
        return !$friend->friends && $friend->user_id === $user->id;
    }

    public function removeFriend(User $user, Friend $friend)
    {
        return $friend->friends && $friend->user_id === $user->id;
    }
}
