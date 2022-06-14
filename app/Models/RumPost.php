<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RumPost extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'rum_id',
        'approved',
        'title',
        'description',
    ];
}
