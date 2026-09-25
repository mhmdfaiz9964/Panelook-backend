<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('address')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('source_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('destination_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('quantity');
            $table->string('status')->default('Draft'); // Draft, Requested, Approved, In Transit, Completed, Cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('warehouses');
    }
};
