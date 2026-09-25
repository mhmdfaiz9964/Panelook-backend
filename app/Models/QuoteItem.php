<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    protected $fillable = ['quote_id', 'product_id', 'product_name', 'quantity', 'unit_price', 'subtotal'];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
