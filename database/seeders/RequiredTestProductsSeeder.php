<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductLaptopModel;
use App\Models\ProductPartNumber;
use App\Models\ProductTag;
use App\Models\ProductImage;

class RequiredTestProductsSeeder extends Seeder
{
    public function run(): void
    {
        $productsData = [
            [
                'sku' => 'DSP-HP15-FHD-IPS',
                'name' => 'HP 15.6" IPS FHD Laptop Display',
                'slug' => 'hp-156-ips-fhd-laptop-display',
                'brand_id' => 1, // HP
                'category_id' => 1, // Laptop Displays
                'product_type_id' => 1, // Laptop Display

                'display_size' => '15.6 inch',
                'pin_type' => '30-Pin',
                'display_type' => 'IPS',
                'touch_type' => 'Non-Touch',
                'resolution' => '1920x1080',
                'selling_price' => 18500,
                'cost_price' => 14000,
                'stock_quantity' => 20,
                'min_stock' => 2,
                'status' => 'active',
                'is_featured' => true,
                'is_new' => true,
                'warranty' => '6 Months Warranty',
                'condition' => 'Grade A+ (100% Genuine)',
                'main_image' => '/images/products/panel-b156xw04.png',
                'panel_number' => 'B156HAN08.0, NV156FHM-N4K',
                'laptop_model' => 'HP Pavilion 15, HP 15-DK, HP 15-CS',
                'models' => ['HP Pavilion 15', 'HP 15-DK', 'HP 15-CS', 'HP 15-eg', 'HP ProBook 450 G8'],
                'parts' => ['B156HAN08.0', 'NV156FHM-N4K', 'LP156WFC-SPDB'],
                'tags' => ['HP', '15.6', 'IPS', '30-Pin', 'FHD', 'Pavilion'],
            ],
            [
                'sku' => 'DSP-APL-MBA13-RET',
                'name' => 'Apple MacBook Air 13.3" Retina Display Assembly',
                'slug' => 'apple-macbook-air-133-retina-display',
                'brand_id' => 7, // Apple
                'category_id' => 1, // Laptop Displays
                'product_type_id' => 6, // MacBook Display
                'display_size' => '13.3 inch',
                'pin_type' => '30-Pin',
                'display_type' => 'IPS',
                'touch_type' => 'Non-Touch',
                'resolution' => '2560x1600',
                'selling_price' => 48500,
                'cost_price' => 38000,
                'stock_quantity' => 8,
                'min_stock' => 2,
                'status' => 'active',
                'is_featured' => true,
                'is_new' => true,
                'warranty' => '6 Months Warranty',
                'condition' => 'Original OEM Assembly',
                'main_image' => '/images/products/panel-lp156wf9.png',
                'panel_number' => '661-16806, 661-16807',
                'laptop_model' => 'MacBook Air 13, MacBook Air A2337, MacBook Pro 13, MacBook Pro A2338',
                'models' => ['MacBook Air 13', 'MacBook Air A2337', 'MacBook Pro 13', 'MacBook Pro A2338', 'MacBook Air M1'],
                'parts' => ['661-16806', '661-16807', 'A2337-LCD'],
                'tags' => ['Apple', 'MacBook', 'A2337', 'A2338', 'Retina', '13.3'],
            ],
            [
                'sku' => 'DSP-DEL-140-TCH',
                'name' => 'Dell Inspiron 14.0" FHD Touch Screen Display',
                'slug' => 'dell-inspiron-140-fhd-touch-screen',
                'brand_id' => 2, // Dell
                'category_id' => 10, // Touch Displays
                'product_type_id' => 2, // Touch Screen
                'display_size' => '14.0 inch',
                'pin_type' => '40-Pin Touch',
                'display_type' => 'IPS',
                'touch_type' => 'Touch',
                'resolution' => '1920x1080',
                'selling_price' => 26500,
                'cost_price' => 21000,
                'stock_quantity' => 12,
                'min_stock' => 2,
                'status' => 'active',
                'is_featured' => true,
                'is_new' => true,
                'warranty' => '6 Months Warranty',
                'condition' => 'Grade A+ Touch Panel',
                'main_image' => '/images/products/panel-n156hce.png',
                'panel_number' => 'B140HAK02.0, LP140WFA-SPD1',
                'laptop_model' => 'Dell Inspiron 14 5400, Dell Inspiron 14 5406 2-in-1, Dell Latitude 5420',
                'models' => ['Dell Inspiron 14 5400', 'Dell Inspiron 14 5406 2-in-1', 'Dell Latitude 5420', 'Dell Inspiron 14'],
                'parts' => ['B140HAK02.0', 'LP140WFA-SPD1'],
                'tags' => ['Dell', '14.0', 'Touch', 'Inspiron', 'IPS', '40-Pin'],
            ],
            [
                'sku' => 'DSP-ACR-173-144',
                'name' => 'Acer Nitro 17.3" 144Hz IPS Gaming Display',
                'slug' => 'acer-nitro-173-144hz-ips-gaming-display',
                'brand_id' => 5, // Acer
                'category_id' => 1, // Laptop Displays
                'product_type_id' => 1, // Laptop Display
                'display_size' => '17.3 inch',
                'pin_type' => '40-Pin',
                'display_type' => 'IPS',
                'touch_type' => 'Non-Touch',
                'resolution' => '1920x1080',
                'refresh_rate' => '144Hz',
                'selling_price' => 34500,
                'cost_price' => 28000,
                'stock_quantity' => 6,
                'min_stock' => 2,
                'status' => 'active',
                'is_featured' => true,
                'is_new' => true,
                'warranty' => '6 Months Warranty',
                'condition' => 'Grade A+ Gaming Display',
                'main_image' => '/images/products/panel-b156xw04.png',
                'panel_number' => 'B173HAN04.0, NV173FHM-N44',
                'laptop_model' => 'Acer Nitro 5 AN517, Acer Predator Helios 300, Acer Aspire 17',
                'models' => ['Acer Nitro 5 AN517', 'Acer Predator Helios 300', 'Acer Aspire 17', 'Acer Nitro 17'],
                'parts' => ['B173HAN04.0', 'NV173FHM-N44'],
                'tags' => ['Acer', '17.3', 'IPS', '144Hz', 'Nitro', 'Gaming'],
            ],
        ];

        foreach ($productsData as $data) {
            $models = $data['models'];
            $parts = $data['parts'];
            $tags = $data['tags'];
            unset($data['models'], $data['parts'], $data['tags']);

            $product = Product::updateOrCreate(
                ['sku' => $data['sku']],
                $data
            );

            // Sync laptop models
            ProductLaptopModel::where('product_id', $product->id)->delete();
            foreach ($models as $m) {
                ProductLaptopModel::create([
                    'product_id' => $product->id,
                    'model_name' => $m,
                ]);
            }

            // Sync part numbers
            ProductPartNumber::where('product_id', $product->id)->delete();
            foreach ($parts as $pt) {
                ProductPartNumber::create([
                    'product_id' => $product->id,
                    'part_number' => $pt,
                ]);
            }

            // Sync tags
            ProductTag::where('product_id', $product->id)->delete();
            foreach ($tags as $t) {
                ProductTag::create([
                    'product_id' => $product->id,
                    'tag' => $t,
                ]);
            }

            // Sync primary image in product_images table
            ProductImage::where('product_id', $product->id)->delete();
            ProductImage::create([
                'product_id' => $product->id,
                'image_url' => $product->main_image,
                'image_type' => 'main',
                'sort_order' => 0,
            ]);
        }
    }
}
