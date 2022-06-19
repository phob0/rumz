<?php

namespace App\Policies;

use App\Models\Rum;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RumPolicy
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

    public function view(User $user, Rum $rum)
    {
        switch ($rum->type) {
            case Rum::TYPE_FREE:
                return true;
            case Rum::TYPE_PRIVATE || Rum::TYPE_CONFIDENTIAL;
                return $rum->users->contains(function ($member) use($user) {
                    return $member->id === $user->id && $member->pivot->granted;
                });
            case Rum::TYPE_PAID;
                // TODO: make date difference to a minimum of 1 month
                return $rum->subscribed->contains(function ($member) use($user) {
                    return $member->id === $user->id
                        && $member->pivot->is_paid
                        && $member->pivot->updated_at->diffInDays(
                            $member->pivot->expire_at
                        ) !== 0;
                });
            default;
                return false;
        }
    }

    public function edit(User $user, Rum $rum)
    {
        return $user->id === $rum->user_id;
    }

    public function update(User $user, Rum $rum)
    {
        return $user->id === $rum->user_id;
    }

    public function delete(User $user, Rum $rum)
    {
        return $user->id === $rum->user_id;
    }
}
