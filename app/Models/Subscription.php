<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'rum_id',
        'transfer_id',
        'amount',
        'profit',
        'owner_amount',
        'is_paid',
        'granted',
        'expire_at',
    ];

    protected $casts = [
        'expire_at' => 'datetime:Y-m-d H:00',
    ];

    public function rum(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Rum::class);
    }

    public function members(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function history_payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(HistoryPayment::class);
    }
}
