<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductModel extends Model
{
    protected $table = 'product_models';

    protected $fillable = ['brand_id', 'model_name', 'model_number', 'series', 'compatible_sizes', 'status'];

    protected $casts = [
        'status' => 'boolean',
        'compatible_sizes' => 'array',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
