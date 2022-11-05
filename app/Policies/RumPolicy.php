<?php

namespace App\Policies;

use App\Models\Rum;
use App\Models\RumPost;
use App\Models\User;
use Carbon\Carbon;
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

    }

    public function view(User $user, Rum $rum)
    {
        switch ($rum->type) {
            case Rum::TYPE_FREE:
                return true;
            case Rum::TYPE_PRIVATE || Rum::TYPE_CONFIDENTIAL;
                return (
                    $rum->user_id === $user->id ||
                    $rum->users->contains(function ($member) use($user) {
                        return $member->id === $user->id && $member->pivot->granted;
                    }) ||
                    $rum->admins->contains(function ($admin) use($user) {
                        return $admin->id === $user->id && $admin->pivot->granted;
                    })
                );
            case Rum::TYPE_PAID;
                // TODO: make date difference to a minimum of 1 month
                return (
                    $rum->subscribed->contains(function ($member) use($user) {
                        return $member->id === $user->id
                            && $member->pivot->is_paid
                            && $member->pivot->updated_at->diffInDays(
                                $member->pivot->expire_at
                            ) !== 0;
                    }) ||
                    $rum->admins->contains(function ($admin) use($user) {
                        return $admin->id === $user->id && $admin->pivot->granted;
                    })
                );
            default;
                return false;
        }
    }

    public function edit(User $user, Rum $rum)
    {
        return $user->id === $rum->user_id ||
            $rum->admins->contains(function ($admin) use($user) {
                return $admin->id === $user->id && $admin->pivot->granted;
            });
    }

    public function update(User $user, Rum $rum)
    {
        return $user->id === $rum->user_id ||
            $rum->admins->contains(function ($admin) use($user) {
                return $admin->id === $user->id && $admin->pivot->granted;
            });;
    }

    public function delete(User $user, Rum $rum)
    {
        return $user->id === $rum->user_id ||
            $rum->admins->contains(function ($admin) use($user) {
                return $admin->id === $user->id && $admin->pivot->granted;
            });;
    }

    public function join(User $user, Rum $rum, $type)
    {
        $has_members = $rum->users->contains(function($item) use($user) { return $item->id === $user->id; });
        $has_subscribers = $rum->subscribed->contains(function($item) use($user) { return $item->id === $user->id; });

        return $user->id !== $rum->user_id
            && ($type === 'private' || $type === 'confidential' ?
                !$has_members
                : ($has_subscribers && $rum->subscriptions->firstWhere('user_id', '=', $user->id)->expire_at->isPast()) || !$has_subscribers
            )
            && $rum->type === $type;
    }

    public function grant(User $master, Rum $rum, User $user)
    {
        return ($rum->user_id === $master->id
            || $rum->join_requests->contains(function($item) use($user) { return $item->user_id === $user->id; })) &&
            !$rum->join_requests->where('rum_id', $rum->id)->where('user_id', $user->id)->first()->granted;
    }

    public function membersList(User $user, Rum $rum)
    {
        return $rum->type !== Rum::TYPE_PAID ?
            $rum->users->contains(fn ($item) => $item->id === $user->id) :
            $rum->subscribed->contains(fn ($item) => $item->id === $user->id)
            || $rum->user_id === $user->id;
    }

    public function inviteMember(User $user, Rum $rum, User $member)
    {
        return $rum->type === Rum::TYPE_CONFIDENTIAL &&
            !$rum->users->contains(fn ($item) => $item->id === $member->id) &&
            $member->id !== $rum->user_id &&
            $user->id === $rum->user_id;
    }

    public function inviteAdminMember(User $user, Rum $rum, User $member)
    {
        return !$rum->admins->contains(fn ($item) => $item->id === $member->id) &&
            $member->id !== $rum->user_id &&
            $user->id === $rum->user_id;
    }

    public function acceptInvite(User $user, Rum $rum)
    {
        return $user->id !== $rum->user_id &&
            $rum->join_requests->contains(fn ($item) => $item->user_id === $user->id);
    }

    public function acceptAdminInvite(User $user, Rum $rum)
    {
        return $user->id !== $rum->user_id &&
            $rum->join_admin_requests->contains(fn ($item) => $item->user_id === $user->id);
    }

    public function banOrUnbanMembers(User $user, Rum $rum, User $member, $action)
    {
        $users = $action === 'ban' ?
            $rum->users->contains(fn ($item) => $item->id === $member->id) :
            $rum->join_requests->contains(fn ($item) => $item->user_id === $member->id);

        $subscribers = $action === 'ban' ?
            $rum->subscribed->contains(fn ($item) => $item->id === $member->id) :
            $rum->subscriptions->contains(fn ($item) => $item->user_id === $member->id);

        return ($rum->type !== Rum::TYPE_PAID ?
            $users :
            $subscribers);
    }

    public function removeMembers(User $user, Rum $rum, User $member)
    {
        return $rum->users->contains(fn ($item) => $item->id === $member->id);
    }
}
