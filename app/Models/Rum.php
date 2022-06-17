<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rum extends Model
{
    use HasFactory,  HasUuid;

    const TYPE_FREE = 'free';
    const TYPE_PAID = 'paid';
    const TYPE_PRIVATE = 'private';
    const TYPE_CONFIDENTIAL = 'confidential';

    const FOR_ME = 'me';
    const FOR_ALL = 'all';
    const FOR_MEMBERS = 'members';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'image',
        'type',
        'privilege',
    ];

    public function users(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            UserRum::class,
            'user_id',
            'id',
            'user_id',
            'user_id'
        );
    }

    public function posts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RumPost::class);
    }
}
