<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create product_types table
        if (!Schema::hasTable('product_types')) {
            Schema::create('product_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('status')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // 2. Add product_type_id to products
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'product_type_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('product_type_id')->nullable()->after('brand_id')->constrained('product_types')->nullOnDelete();
            });
        }

        // 3. Create product_laptop_models
        if (!Schema::hasTable('product_laptop_models')) {
            Schema::create('product_laptop_models', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('model_name')->index();
                $table->timestamps();
            });
        }

        // 4. Create product_part_numbers
        if (!Schema::hasTable('product_part_numbers')) {
            Schema::create('product_part_numbers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('part_number')->index();
                $table->timestamps();
            });
        }

        // 5. Create product_tags
        if (!Schema::hasTable('product_tags')) {
            Schema::create('product_tags', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('tag')->index();
                $table->timestamps();
            });
        }

        // 6. Create product_images
        if (!Schema::hasTable('product_images')) {
            Schema::create('product_images', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('image_url');
                $table->string('image_type')->default('gallery'); // main, gallery, drawing
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // 7. Seed standard product types
        $types = [
            ['name' => 'Laptop Display', 'slug' => 'laptop-display', 'sort_order' => 1],
            ['name' => 'Touch Screen', 'slug' => 'touch-screen', 'sort_order' => 2],
            ['name' => 'LCD Panel', 'slug' => 'lcd-panel', 'sort_order' => 3],
            ['name' => 'LED Panel', 'slug' => 'led-panel', 'sort_order' => 4],
            ['name' => 'OLED Panel', 'slug' => 'oled-panel', 'sort_order' => 5],
            ['name' => 'MacBook Display', 'slug' => 'macbook-display', 'sort_order' => 6],
            ['name' => 'Display Assembly', 'slug' => 'display-assembly', 'sort_order' => 7],
            ['name' => 'Screen Assembly', 'slug' => 'screen-assembly', 'sort_order' => 8],
        ];

        foreach ($types as $t) {
            DB::table('product_types')->updateOrInsert(
                ['slug' => $t['slug']],
                array_merge($t, [
                    'status' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $laptopDisplayType = DB::table('product_types')->where('slug', 'laptop-display')->first();

        // 8. Seed Brand 'Apple'
        DB::table('brands')->updateOrInsert(
            ['slug' => 'apple'],
            [
                'name' => 'Apple',
                'slug' => 'apple',
                'description' => 'Original replacement retina & liquid retina panels for Apple MacBook Air & MacBook Pro.',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 9. Standardize and Seed 15 Master Screen Sizes
        // First delete non-standard legacy labels that might conflict
        DB::table('sizes')->whereIn('label', ['14"', '16"'])->delete();

        $masterSizes = [
            '10.1"', '11.6"', '12.0"', '12.5"', '13.3"', '13.4"',
            '14.0"', '14.5"', '15.0"', '15.6"', '16.0"', '16.1"',
            '17.0"', '17.3"', '18.0"'
        ];

        foreach ($masterSizes as $index => $label) {
            DB::table('sizes')->updateOrInsert(
                ['label' => $label],
                [
                    'sort_order' => $index + 1,
                    'status' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 10. Data Migration: Extract existing models, part numbers, images from products
        $products = DB::table('products')->get();
        foreach ($products as $p) {
            // Assign default product type if none
            if (!$p->product_type_id && $laptopDisplayType) {
                DB::table('products')->where('id', $p->id)->update([
                    'product_type_id' => $laptopDisplayType->id
                ]);
            }

            // Migrate laptop models
            if (!empty($p->laptop_model)) {
                $rawModels = preg_split('/[\/,;|\n]+/', $p->laptop_model);
                foreach ($rawModels as $m) {
                    $cleanModel = trim($m);
                    if (!empty($cleanModel)) {
                        DB::table('product_laptop_models')->updateOrInsert(
                            ['product_id' => $p->id, 'model_name' => $cleanModel],
                            ['created_at' => now(), 'updated_at' => now()]
                        );
                    }
                }
            }

            // Migrate panel number as part number
            if (!empty($p->panel_number)) {
                DB::table('product_part_numbers')->updateOrInsert(
                    ['product_id' => $p->id, 'part_number' => trim($p->panel_number)],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            // Migrate main image
            if (!empty($p->main_image)) {
                DB::table('product_images')->updateOrInsert(
                    ['product_id' => $p->id, 'image_url' => $p->main_image, 'image_type' => 'main'],
                    ['sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]
                );
            }

            // Migrate gallery images
            if (!empty($p->images)) {
                $gallery = json_decode($p->images, true);
                if (is_array($gallery)) {
                    foreach ($gallery as $idx => $img) {
                        if (!empty($img) && $img !== $p->main_image) {
                            DB::table('product_images')->updateOrInsert(
                                ['product_id' => $p->id, 'image_url' => $img, 'image_type' => 'gallery'],
                                ['sort_order' => $idx + 1, 'created_at' => now(), 'updated_at' => now()]
                            );
                        }
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_tags');
        Schema::dropIfExists('product_part_numbers');
        Schema::dropIfExists('product_laptop_models');

        if (Schema::hasColumn('products', 'product_type_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropForeign(['product_type_id']);
                $table->dropColumn('product_type_id');
            });
        }

        Schema::dropIfExists('product_types');
    }
};
