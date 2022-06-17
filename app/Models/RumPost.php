<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RumPost extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'rum_id',
        'approved',
        'title',
        'description',
    ];

    protected $withCount = [
        'likes',
        'comments'
    ];

    protected $with = [
        'usersLike',
        'comments'
    ];

    public function likes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Like::class, 'post_id', 'id');
    }

    public function usersLike(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            Like::class,
            'post_id',
            'id',
            'id',
            'user_id'
        );
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Comment::class, 'post_id', 'id');
    }
}
