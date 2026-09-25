<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update users table for roles & phone
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('customer')->after('email');
                $table->string('phone')->nullable()->after('role');
                $table->text('address')->nullable()->after('phone');
            }
        });

        // Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->boolean('status')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Brands
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // Products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('main_image')->nullable();
            $table->json('images')->nullable();
            $table->string('status')->default('active'); // active, draft, archived
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new')->default(false);
            $table->boolean('is_sale')->default(false);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock')->default(2);
            
            // Specific Laptop Display attributes
            $table->string('panel_number')->nullable()->index();
            $table->string('laptop_model')->nullable()->index();
            $table->string('display_size')->nullable()->index(); // e.g. 14", 15.6", 17.3"
            $table->string('pin_type')->nullable()->index(); // 30-pin, 40-pin
            $table->string('display_type')->nullable()->index(); // IPS, TN, OLED
            $table->string('touch_type')->nullable()->index(); // Touch, Non-Touch
            $table->string('resolution')->nullable(); // 1366x768, 1920x1080 FHD
            $table->string('refresh_rate')->nullable(); // 60Hz, 120Hz, 144Hz
            $table->string('connector')->nullable();
            $table->text('compatibility')->nullable();
            $table->string('warranty')->default('3-12 Months');
            $table->string('condition')->default('Brand New Genuine');
            $table->timestamps();
        });

        // Product Variations
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('panel_number')->nullable()->index();
            $table->string('laptop_model')->nullable()->index();
            $table->string('brand')->nullable();
            $table->string('display_size')->nullable();
            $table->string('pin_type')->nullable();
            $table->string('touch_type')->nullable();
            $table->string('display_type')->nullable();
            $table->string('resolution')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(2);
            $table->string('image')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // Inventory Transactions
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
            $table->integer('previous_quantity');
            $table->integer('change_quantity');
            $table->integer('new_quantity');
            $table->string('reason'); // stock_in, sale, adjustment, damaged, returned
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        // Carts & Items
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->timestamps();
        });

        // Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('invoice_number')->unique()->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->text('shipping_address');
            $table->string('city');
            $table->string('district');
            $table->string('postal_code')->nullable();
            $table->text('notes')->nullable();
            $table->string('payment_method')->default('COD'); // COD, BankTransfer, Online
            $table->string('payment_status')->default('Pending'); // Pending, Paid, Failed, Refunded
            $table->string('shipping_status')->default('Pending'); // Pending, Processing, Shipped, Delivered
            $table->string('order_status')->default('Pending'); // Pending, Confirmed, Processing, Packed, Shipped, Delivered, Cancelled, Returned
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->timestamps();
        });

        // Order Items
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
            $table->string('product_name');
            $table->json('variation_details')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        // Order Status History
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('status');
            $table->text('comment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Homepage Sections & Banners
        Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_key');
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->json('content')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // SEO Metadata
        Schema::create('seo_metadata', function (Blueprint $table) {
            $table->id();
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('canonical_url')->nullable();
            $table->timestamps();
        });

        // Settings
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('seo_metadata');
        Schema::dropIfExists('homepage_sections');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('products');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
