<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PanelPin extends Model
{
    protected $fillable = ['name', 'pin_count', 'description', 'sort_order', 'status'];

    protected $casts = ['status' => 'boolean'];
}
