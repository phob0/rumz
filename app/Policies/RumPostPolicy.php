<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Rum;
use App\Models\User;
use App\Models\RumPost;
use Illuminate\Auth\Access\HandlesAuthorization;

class RumPostPolicy
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

    public function create(User $user, $rum_id)
    {
        // get rum
        // check privilege
        // return response
        return true;
    }

    public function edit(User $user, RumPost $rumPost)
    {
        return $user->id === $rumPost->user_id;
    }

    public function update(User $user, RumPost $rumPost)
    {
        return $user->id === $rumPost->user_id;
    }

    public function likeOrComment(User $user, RumPost $rumPost)
    {
        switch ($rumPost->rum->type) {
            case Rum::TYPE_PAID:
                return $rumPost->rum->subscribed->contains(function ($record) use($user){
                    return $record->user_id === $user->id;
                });
            default:
                return $rumPost->rum->type !== Rum::TYPE_PAID ?
                    $rumPost->rum->users->contains(function ($record) use($user){
                        return $record->user_id === $user->id;
                    }) || ($rumPost->rum->user_id === $user->id || $rumPost->user_id === $user->id) : false;
        }
    }

    public function updateOrDeleteComment(User $user, RumPost $rumPost, Comment $comment)
    {
        return $comment->user_id === $user->id ||
            ($comment->user_id !== $user->id && $comment->post->rum->user_id === $user->id);
    }

    public function updateOrDeleteReply(User $user, RumPost $rumPost, Comment $comment, $reply_id)
    {
        return ($comment->user_id === $user->id ||
            ($comment->user_id !== $user->id && $comment->post->rum->user_id === $user->id))
            && collect($comment->reply)->contains(fn ($item) => $item['id'] === $reply_id);
    }

    public function reportReply(User $user, RumPost $rumPost, Comment $comment, $reply_id)
    {
        return ($rumPost->rum->subscribed->contains(function ($record) use($user){
                return $record->user_id === $user->id;
            }) ||
            $rumPost->rum->users->contains(function ($record) use($user){
                return $record->user_id === $user->id;
            })) && collect($comment->reply)->contains(fn ($item) => $item['id'] === $reply_id);
    }

    public function reportComment(User $user, RumPost $rumPost, Comment $comment)
    {
        return ($rumPost->rum->subscribed->contains(function ($record) use($user){
                    return $record->user_id === $user->id;
                }) ||
                $rumPost->rum->users->contains(function ($record) use($user){
                    return $record->user_id === $user->id;
                }));
    }

    public function reportPost(User $user, RumPost $rumPost)
    {
        return ($rumPost->rum->subscribed->contains(function ($record) use($user){
                return $record->user_id === $user->id;
            }) ||
            $rumPost->rum->users->contains(function ($record) use($user){
                return $record->user_id === $user->id;
            }));
    }

}
