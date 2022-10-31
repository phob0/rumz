<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\Favourite;
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
        // TODO: check privilege
        return true;
    }

    public function edit(User $user, RumPost $rumPost)
    {
        return $user->id === $rumPost->user_id;
    }

    public function update(User $user, RumPost $rumPost)
    {
        return $rumPost->rum->admins->contains(function ($record) use($user){
                return $record->user_id === $user->id;
            }) || ($user->id === $rumPost->rum->user_id) || ($user->id === $rumPost->user_id);
    }

    public function delete(User $user, RumPost $rumPost)
    {
        return $rumPost->rum->admins->contains(function ($record) use($user){
                return $record->user_id === $user->id;
            }) || ($user->id === $rumPost->rum->user_id) || ($user->id === $rumPost->user_id);
    }

    public function comment(User $user, RumPost $rumPost)
    {
        switch ($rumPost->rum->type) {
            case Rum::TYPE_PAID:
                return $rumPost->rum->subscribed->contains(function ($record) use($user){
                    return $record->user_id === $user->id;
                }) || ($rumPost->rum->user_id === $user->id || $rumPost->user_id === $user->id);
            default:
                return $rumPost->rum->type !== Rum::TYPE_PAID ?
                    $rumPost->rum->users->contains(function ($record) use($user){
                        return $record->id === $user->id;
                    }) || ($rumPost->rum->user_id === $user->id || $rumPost->user_id === $user->id) : false;
        }
    }

    public function likeOrDislike(User $user, $model, $type)
    {
        $rum = $type === 'post' ?
                $model->rum :
                ($type === 'comment' ?
                    $model->post->rum :
                    $model->parent->post->rum
                );

        switch ($rum->type) {
            case Rum::TYPE_PAID:
                return $rum->subscribed->contains(function ($record) use($user){
                    return $record->user_id === $user->id;
                }) || ($rum->user_id === $user->id || $model->user_id === $user->id);
            default:
                return $rum->type !== Rum::TYPE_PAID ?
                    $rum->users->contains(function ($record) use($user){
                        return $record->id === $user->id;
                    }) || ($rum->user_id === $user->id || $model->user_id === $user->id) : false;
        }
    }

    public function updateOrDeleteComment(User $user, RumPost $rumPost, Comment $comment)
    {
        return $comment->user_id === $user->id ||
            ($comment->user_id !== $user->id && $comment->post->rum->user_id === $user->id);
    }

    public function updateOrDeleteReply(User $user, RumPost $rumPost, Comment $comment, CommentReply $commentReply)
    {
        return ($commentReply->user_id === $user->id ||
            ($commentReply->user_id !== $user->id && $comment->post->rum->user_id === $user->id))
            && $comment->replies->contains(fn ($item) => $item->id === $commentReply->id);
    }

    public function reportReply(User $user, RumPost $rumPost, Comment $comment, CommentReply $commentReply)
    {
        return ($rumPost->rum->subscribed->contains(function ($record) use($user){
                return $record->user_id === $user->id;
            }) ||
            $rumPost->rum->users->contains(function ($record) use($user){
                return $record->id === $user->id;
            })) && $comment->replies->contains(fn ($item) => $item->id === $commentReply->id);
    }

    public function reportComment(User $user, RumPost $rumPost, Comment $comment)
    {
        return ($rumPost->rum->subscribed->contains(function ($record) use($user){
                    return $record->user_id === $user->id;
                }) ||
                $rumPost->rum->users->contains(function ($record) use($user){
                    return $record->id === $user->id;
                }));
    }

    public function reportPost(User $user, RumPost $rumPost)
    {
        return ($rumPost->rum->subscribed->contains(function ($record) use($user){
                return $record->user_id === $user->id;
            }) ||
            $rumPost->rum->users->contains(function ($record) use($user){
                return $record->id === $user->id;
            }));
    }

    public function saveFavourite(User $user, RumPost $rumPost)
    {
        return ($rumPost->rum->subscribed->contains(function ($record) use($user){
                return $record->user_id === $user->id;
            }) ||
            $rumPost->rum->users->contains(function ($record) use($user){
                return $record->id === $user->id;
            }));
    }

    public function removeFavourite(User $user, RumPost $rumPost, Favourite $favourite)
    {
        return ($rumPost->rum->subscribed->contains(function ($record) use($user){
                return $record->user_id === $user->id;
            }) ||
            $rumPost->rum->users->contains(function ($record) use($user){
                return $record->id === $user->id;
            })) &&
            $favourite->user_id === $user->id &&
            $favourite->post_id === $rumPost->id;
    }

    // TODO: add check for multiple admins

}
