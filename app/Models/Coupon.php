<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'discount_type', 'discount_value', 'min_order', 'max_discount',
        'start_date', 'end_date', 'usage_limit', 'per_customer_limit', 'used_count', 'status',
    ];

    protected $casts = ['status' => 'boolean'];
}
