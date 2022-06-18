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

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            UserRum::class,
        )->withPivot('granted');
    }

    public function posts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RumPost::class);
    }

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function subscribed(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            Subscription::class,
        )->withPivot(['amount', 'is_paid', 'expire_at', 'created_at', 'updated_at']);
    }
}
