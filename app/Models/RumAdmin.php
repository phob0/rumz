<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RumAdmin extends Model
{
    use HasFactory;

    protected $table = 'admins_rums';

    protected $fillable = [
        'user_id',
        'rum_id',
        'granted',
    ];
}
