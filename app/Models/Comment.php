<?php

namespace App\Models;

use App\Casts\JsonCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class Comment extends Model
{
    use \Backpack\CRUD\app\Models\Traits\CrudTrait;
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id',
        'post_id',
        'comment',
    ];

    protected $withCount = [
        'likes',
        'dislikes',
        'replies'
    ];

    protected $with = [
        'user',
        'post',
        'replies'
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function post(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RumPost::class, 'post_id', 'id');
    }

    public function likes(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function dislikes(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Dislike::class, 'dislikeable');
    }

    public function replies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CommentReply::class);
    }
}
