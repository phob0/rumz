<?php

namespace App\Models;

use App\Traits\HasUuid;
use Faker\Generator;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rum extends Model
{
    use \Backpack\CRUD\app\Models\Traits\CrudTrait;
    use HasFactory,  HasUuid, SoftDeletes;

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
        'website',
        'type',
        'privilege',
    ];

    protected $appends = [
        'members'
    ];

    protected $with = [
        'hashtags',
        'users',
        'subscribed',
        'image'
    ];

    public function image(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    public function admins()
    {
        return $this->belongsToMany(
            User::class,
            RumAdmin::class,
        )->withPivot('granted')->where('granted', 1);
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            UserRum::class,
        )->withPivot('granted')->where('granted', 1);
    }

    public function joined(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserRum::class)->where('granted', 1);
    }

    public function join_requests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserRum::class)->where('granted', 0);
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
            Subscription::class
        )->withPivot(['amount', 'is_paid', 'granted', 'expire_at', 'created_at', 'updated_at'])->where('granted', 1);
    }

    public function master(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function hashtags(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RumHashtag::class, 'rum_id', 'id');
    }

    public function getMembersAttribute()
    {
        return $this->users->concat($this->subscribed)->count();
    }

}
