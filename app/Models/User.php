<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasUuid;

class User extends Authenticatable
{
    use \Backpack\CRUD\app\Models\Traits\CrudTrait;
    use HasApiTokens, HasFactory, Notifiable, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'sex',
        'birth_date',
        'description',
        'settings',
        'superadmin',
        'email',
        'password',
        'stripe_id',
        'pm_type',
        'stripe_onboarding'
    ];

    protected $with = [
        'image'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function image(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    public function rums(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Rum::class, 'user_id', 'id');
    }

    public function posts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RumPost::class, 'user_id', 'id');
    }

    public function joinedRums(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            Rum::class,
            UserRum::class
        )->withPivot('granted')->where('granted', 1);
    }

    public function subscribedRums(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            Rum::class,
            Subscription::class,
        )->withPivot(['amount', 'is_paid', 'granted', 'expire_at', 'created_at', 'updated_at'])->where('granted', 1);
    }

    public function hasFriends(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            Friend::class,
            'user_id',
            'friend_id'
        )->where('friends', 1);
    }

    public function isFriends(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            Friend::class,
            'friend_id',
            'user_id'
        )->where('friends', 1);
    }

    public function getFriendsAttribute($value)
    {
        $hasFriends = $this->hasFriends;

        $isFriends = $this->isFriends;

        return $hasFriends->merge($isFriends)->unique();
    }

    public function favourites(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            RumPost::class,
            Favourite::class,
            'post_id',
            'id',
            'id'
        );
    }

    public static function superadmins()
    {
        return self::where('superadmin', 1)->get();
    }
}
