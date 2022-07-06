<?php

namespace App\Models;

use App\Casts\JsonCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class Comment extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id',
        'post_id',
        'comment',
        'reply'
    ];

    protected $with = [
        'user'
    ];

    protected $casts = [
        'reply' => JsonCast::class
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function post(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RumPost::class, 'post_id', 'id');
    }
}
