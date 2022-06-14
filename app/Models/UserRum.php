<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRum extends Model
{
    protected $table = 'users_rums';

    protected $fillable = ['granted'];

    protected $casts = ['granted'];
}
