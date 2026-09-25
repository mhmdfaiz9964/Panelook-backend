<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'product_id', 'variation_id', 'previous_quantity',
        'change_quantity', 'new_quantity', 'reason', 'user_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
