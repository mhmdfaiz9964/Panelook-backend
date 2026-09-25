<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'panel_number',
        'laptop_model',
        'brand',
        'display_size',
        'pin_type',
        'touch_type',
        'display_type',
        'resolution',
        'price',
        'sale_price',
        'cost_price',
        'stock_quantity',
        'low_stock_threshold',
        'image',
        'status',
    ];

    protected $casts = [
        'price' => 'float',
        'sale_price' => 'float',
        'cost_price' => 'float',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
