<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $fillable = [
        'reference', 'source_warehouse_id', 'destination_warehouse_id',
        'product_id', 'quantity', 'status', 'notes', 'created_by',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sourceWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }
}
