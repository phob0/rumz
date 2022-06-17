<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserRum extends Pivot
{
    use HasFactory;

    protected $table = 'users_rums';

    protected $fillable = ['granted'];

    protected $casts = ['granted'];

    public function rums()
    {
        return $this->hasMany(Rum::class, 'id', 'rum_id');
    }
}
