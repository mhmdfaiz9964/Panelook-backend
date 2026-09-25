<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Purchases
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Sri Lanka');
            $table->string('tax_id')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('shipping', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('status')->default('Draft'); // Draft, Ordered, Partially Received, Received, Cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('quantity');
            $table->integer('received_quantity')->default(0);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->date('received_date');
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_receipt_id')->constrained('purchase_receipts')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items')->cascadeOnDelete();
            $table->integer('received_quantity');
            $table->timestamps();
        });

        // Marketing
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('desktop_image')->nullable();
            $table->string('mobile_image')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('discount_type')->default('percentage'); // percentage, fixed
            $table->decimal('discount_value', 12, 2);
            $table->decimal('min_order', 12, 2)->nullable();
            $table->decimal('max_discount', 12, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('per_customer_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('flash_sale'); // flash_sale, weekend_sale, brand_sale, category_sale, clearance
            $table->text('description')->nullable();
            $table->decimal('discount_value', 12, 2)->nullable();
            $table->string('discount_type')->default('percentage');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('banner_image')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // Quotes
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number')->unique();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('shipping', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('Draft'); // Draft, Sent, Accepted, Expired, Converted
            $table->foreignId('converted_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        // WhatsApp Orders
        Schema::create('whatsapp_orders', function (Blueprint $table) {
            $table->id();
            $table->string('whatsapp_id')->nullable();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->text('product_inquiry');
            $table->integer('quantity')->default(1);
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('status')->default('New'); // New, Contacted, Confirmed, Processing, Completed, Cancelled
            $table->foreignId('converted_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamps();
        });

        // Activity Logs
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('module');
            $table->string('record_label')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('whatsapp_orders');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('purchase_receipt_items');
        Schema::dropIfExists('purchase_receipts');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
    }
};
