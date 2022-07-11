<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentReply extends Model
{
    use HasFactory, HasUuid;

    public function likes(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function dislikes(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Dislike::class, 'dislikeable');
    }
}
