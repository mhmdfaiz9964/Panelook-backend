<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    protected $fillable = ['label', 'sort_order', 'status'];

    protected $casts = ['status' => 'boolean'];
}
