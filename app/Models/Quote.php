<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    protected $fillable = [
        'quote_number', 'customer_name', 'customer_phone', 'customer_email',
        'subtotal', 'discount', 'shipping', 'total', 'notes', 'expiry_date',
        'status', 'converted_order_id',
    ];

    public function items()
    {
        return $this->hasMany(QuoteItem::class);
    }
}
