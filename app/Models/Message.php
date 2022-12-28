<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'id', 
        'user_id', 
        'channel',
        'mesage'
    ];
}
