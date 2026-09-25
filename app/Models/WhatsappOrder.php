<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappOrder extends Model
{
    protected $table = 'whatsapp_orders';

    protected $fillable = [
        'whatsapp_id', 'customer_name', 'customer_phone', 'product_inquiry',
        'quantity', 'amount', 'status', 'converted_order_id',
    ];
}
