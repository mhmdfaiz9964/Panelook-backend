<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'name', 'type', 'description', 'discount_value', 'discount_type',
        'start_date', 'end_date', 'banner_image', 'status',
    ];

    protected $casts = ['status' => 'boolean'];
}
