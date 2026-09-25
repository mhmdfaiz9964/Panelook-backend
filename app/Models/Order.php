<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'invoice_number',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'city',
        'district',
        'postal_code',
        'province',
        'shipping_method_id',
        'shipping_method_name',
        'courier_name',
        'tracking_number',
        'idempotency_key',
        'coupon_code',
        'notes',
        'payment_method',
        'payment_status',
        'shipping_status',
        'order_status',
        'subtotal',
        'shipping_cost',
        'discount',
        'grand_total',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'shipping_cost' => 'float',
        'discount' => 'float',
        'grand_total' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }

    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }
}
