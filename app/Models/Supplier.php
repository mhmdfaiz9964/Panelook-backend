<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name', 'contact_person', 'phone', 'email', 'address',
        'city', 'country', 'tax_id', 'notes', 'status',
    ];

    protected $casts = ['status' => 'boolean'];

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
