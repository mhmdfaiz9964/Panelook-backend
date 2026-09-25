<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sizes', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique(); // e.g. 15.6"
            $table->integer('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('panel_pins', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. 30-Pin
            $table->integer('pin_count')->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('product_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('model_name');
            $table->string('model_number')->nullable();
            $table->string('series')->nullable();
            $table->json('compatible_sizes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('select'); // text, number, select, multiselect, boolean
            $table->json('values')->nullable();
            $table->boolean('required')->default(false);
            $table->boolean('filterable')->default(true);
            $table->integer('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('product_models');
        Schema::dropIfExists('panel_pins');
        Schema::dropIfExists('sizes');
    }
};
