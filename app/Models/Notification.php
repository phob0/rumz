<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
    ];

    protected $dates = ['deleted_at'];

    public function notifiable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'notifiable_type', 'notifiable_id');
    }
}
