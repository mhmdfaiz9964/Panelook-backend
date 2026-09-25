<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title', 'subtitle', 'desktop_image', 'mobile_image', 'button_text',
        'button_url', 'start_date', 'end_date', 'sort_order', 'status',
    ];

    protected $casts = ['status' => 'boolean'];
}
