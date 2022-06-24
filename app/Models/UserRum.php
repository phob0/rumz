<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserRum extends Model
{
    use HasFactory;

    protected $table = 'users_rums';

    protected $fillable = [
        'user_id',
        'rum_id',
        'granted'
    ];

    public function rum(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Rum::class, 'id', 'rum_id');
    }

    public function subscriber(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
