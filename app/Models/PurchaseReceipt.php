<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReceipt extends Model
{
    protected $fillable = ['receipt_number', 'purchase_order_id', 'received_date', 'notes', 'received_by'];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseReceiptItem::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
