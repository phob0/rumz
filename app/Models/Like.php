<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Like extends Pivot
{
    use HasFactory;

    protected $table = 'likes';

    public function likeable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function posts(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(RumPost::class, 'likeable');
    }

    public function comment(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(Comment::class, 'likeable');
    }

    public function comment_reply(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(CommentReply::class, 'likeable');
    }
}
