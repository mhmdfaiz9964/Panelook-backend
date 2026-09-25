<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_zone_id',
        'name',
        'code',
        'description',
        'price',
        'estimated_days',
        'cod_supported',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cod_supported' => 'boolean',
        'status' => 'boolean',
    ];

    public function zone()
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }
}
