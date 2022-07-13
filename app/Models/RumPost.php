<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class RumPost extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'rum_id',
        'user_id',
        'approved',
        'title',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    protected $withCount = [
        'likes',
        'comments'
    ];

    protected $with = [
        'usersLike',
        'comments'
    ];

    public function likes(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function dislikes(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Dislike::class, 'dislikeable');
    }

    public function usersLike(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            Like::class,
            'likeable_id',
            'id',
            'id',
            'user_id'
        )->where(
            'likeable_type',
            array_search(static::class, Relation::morphMap()) ?: static::class
        );

    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Comment::class, 'post_id', 'id');
    }

    public function comment_replies(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            CommentReply::class,
            Comment::class,
            'post_id',
            'comment_id',
            'id',
            'id'
        );
    }

    public function rum(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Rum::class, 'rum_id', 'id');
    }

    public function master(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

}
