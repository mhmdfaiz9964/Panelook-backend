<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = ['name', 'type', 'values', 'required', 'filterable', 'sort_order', 'status'];

    protected $casts = [
        'values' => 'array',
        'required' => 'boolean',
        'filterable' => 'boolean',
        'status' => 'boolean',
    ];
}
