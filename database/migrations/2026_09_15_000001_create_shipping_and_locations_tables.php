<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Provinces
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 10)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. Districts
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained('provinces')->cascadeOnDelete();
            $table->string('name')->unique();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 3. Cities
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->string('name');
            $table->string('postal_code', 20)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['district_id', 'name']);
        });

        // 4. Shipping Zones
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Colombo Central, Western Province, Outstation, Remote
            $table->decimal('base_price', 12, 2)->default(650);
            $table->decimal('free_shipping_min', 12, 2)->nullable();
            $table->boolean('cod_available')->default(true);
            $table->string('estimated_days')->default('2-3 Business Days');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // 5. Shipping Methods
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->nullable()->constrained('shipping_zones')->nullOnDelete();
            $table->string('name'); // e.g. Standard Delivery, Express Delivery, Same Day Colombo, Store Pickup
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(650);
            $table->string('estimated_days')->default('2-4 Business Days');
            $table->boolean('cod_supported')->default(true);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // 6. Couriers
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Domex, Certis Lanka, Pronto, Koombiyo, Prompt Xpress
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('tracking_url')->nullable(); // e.g. https://www.domex.lk/tracking?ref={TRACKING}
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // 7. Shipments
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_number')->unique();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('courier_id')->nullable()->constrained('couriers')->nullOnDelete();
            $table->foreignId('shipping_method_id')->nullable()->constrained('shipping_methods')->nullOnDelete();
            $table->string('tracking_number')->nullable()->index();
            $table->decimal('shipping_fee', 12, 2)->default(0);
            $table->string('status')->default('Pending'); // Pending, Ready to Pack, Packed, Dispatched, In Transit, Delivered, Failed, Returned
            $table->date('pickup_date')->nullable();
            $table->date('dispatch_date')->nullable();
            $table->date('delivered_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 8. Shipment Status Histories
        Schema::create('shipment_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->string('status');
            $table->text('comment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 9. User Saved Addresses
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type')->default('Home'); // Home, Work, Other
            $table->string('full_name');
            $table->string('phone');
            $table->foreignId('province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->text('delivery_instructions')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // 10. Update Orders with shipping tracking & idempotency
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'province')) {
                $table->string('province')->nullable()->after('district');
            }
            if (!Schema::hasColumn('orders', 'shipping_method_id')) {
                $table->foreignId('shipping_method_id')->nullable()->after('postal_code')->constrained('shipping_methods')->nullOnDelete();
                $table->string('shipping_method_name')->nullable()->after('shipping_method_id');
                $table->string('courier_name')->nullable()->after('shipping_status');
                $table->string('tracking_number')->nullable()->after('courier_name');
                $table->string('idempotency_key')->nullable()->unique()->after('notes');
                $table->string('coupon_code')->nullable()->after('discount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shipping_method_id']);
            $table->dropColumn([
                'province',
                'shipping_method_id',
                'shipping_method_name',
                'courier_name',
                'tracking_number',
                'idempotency_key',
                'coupon_code'
            ]);
        });

        Schema::dropIfExists('user_addresses');
        Schema::dropIfExists('shipment_status_histories');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('couriers');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('shipping_zones');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('provinces');
    }
};
