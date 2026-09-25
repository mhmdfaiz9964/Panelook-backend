<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'base_price',
        'free_shipping_min',
        'cod_available',
        'estimated_days',
        'status',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'free_shipping_min' => 'decimal:2',
        'cod_available' => 'boolean',
        'status' => 'boolean',
    ];

    public function methods()
    {
        return $this->hasMany(ShippingMethod::class);
    }
}
