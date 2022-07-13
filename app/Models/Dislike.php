<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Dislike extends Pivot
{
    use HasFactory;

    protected $table = 'dislikes';

    public function dislikeable()
    {
        return $this->morphTo();
    }

    public function posts(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(RumPost::class, 'dislikeable');
    }

    public function comment(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(Comment::class, 'dislikeable');
    }

    public function comment_reply(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(CommentReply::class, 'dislikeable');
    }
}
