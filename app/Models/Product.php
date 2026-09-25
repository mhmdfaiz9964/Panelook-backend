<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'category_id',
        'brand_id',
        'product_type_id',
        'description',
        'short_description',
        'main_image',
        'images',
        'status',
        'is_featured',
        'is_new',
        'is_sale',
        'cost_price',
        'selling_price',
        'sale_price',
        'stock_quantity',
        'min_stock',
        'panel_number',
        'laptop_model',
        'display_size',
        'pin_type',
        'display_type',
        'touch_type',
        'resolution',
        'refresh_rate',
        'connector',
        'compatibility',
        'warranty',
        'condition',
    ];

    protected $casts = [
        'images' => 'array',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_sale' => 'boolean',
        'cost_price' => 'float',
        'selling_price' => 'float',
        'sale_price' => 'float',
        'stock_quantity' => 'integer',
        'min_stock' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function laptopModels()
    {
        return $this->hasMany(ProductLaptopModel::class);
    }

    public function partNumbers()
    {
        return $this->hasMany(ProductPartNumber::class);
    }

    public function tags()
    {
        return $this->hasMany(ProductTag::class);
    }

    public function imagesRelation()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order', 'asc');
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function seoMetadata()
    {
        return $this->morphOne(SeoMetadata::class, 'model');
    }
}
