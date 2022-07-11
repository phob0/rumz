<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Dislike extends Pivot
{
    use HasFactory;

    protected $table = 'dislikes';

    public function dislikeable()
    {
        return $this->morphTo();
    }
}
