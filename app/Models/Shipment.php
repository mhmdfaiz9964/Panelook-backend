<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_number',
        'order_id',
        'courier_id',
        'shipping_method_id',
        'tracking_number',
        'shipping_fee',
        'status',
        'pickup_date',
        'dispatch_date',
        'delivered_date',
        'notes',
    ];

    protected $casts = [
        'shipping_fee' => 'decimal:2',
        'pickup_date' => 'date',
        'dispatch_date' => 'date',
        'delivered_date' => 'date',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }

    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(ShipmentStatusHistory::class)->orderBy('created_at', 'desc');
    }

    public function getTrackingLinkAttribute()
    {
        if (!$this->tracking_number || !$this->courier || !$this->courier->tracking_url) {
            return null;
        }
        return str_replace('{TRACKING}', $this->tracking_number, $this->courier->tracking_url);
    }
}
