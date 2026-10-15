<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Helper to safely check if an index exists on a table in MySQL.
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $database = DB::connection()->getDatabaseName();
        $count = DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->count();

        return $count > 0;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            // Drop indexes on columns being converted to longText if they exist
            if ($this->hasIndex('products', 'products_laptop_model_index')) {
                Schema::table('products', function (Blueprint $table) {
                    $table->dropIndex('products_laptop_model_index');
                });
            }

            if ($this->hasIndex('products', 'products_panel_number_index')) {
                Schema::table('products', function (Blueprint $table) {
                    $table->dropIndex('products_panel_number_index');
                });
            }

            // Update column types to LONGTEXT while preserving nullability
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'laptop_model')) {
                    $table->longText('laptop_model')->nullable()->change();
                }
                if (Schema::hasColumn('products', 'panel_number')) {
                    $table->longText('panel_number')->nullable()->change();
                }
                if (Schema::hasColumn('products', 'description')) {
                    $table->longText('description')->nullable()->change();
                }
                if (Schema::hasColumn('products', 'short_description')) {
                    $table->longText('short_description')->nullable()->change();
                }
                if (Schema::hasColumn('products', 'compatibility')) {
                    $table->longText('compatibility')->nullable()->change();
                }
            });
        }

        if (Schema::hasTable('product_variations')) {
            // Drop indexes on product_variations columns being converted to longText
            if ($this->hasIndex('product_variations', 'product_variations_laptop_model_index')) {
                Schema::table('product_variations', function (Blueprint $table) {
                    $table->dropIndex('product_variations_laptop_model_index');
                });
            }

            if ($this->hasIndex('product_variations', 'product_variations_panel_number_index')) {
                Schema::table('product_variations', function (Blueprint $table) {
                    $table->dropIndex('product_variations_panel_number_index');
                });
            }

            Schema::table('product_variations', function (Blueprint $table) {
                if (Schema::hasColumn('product_variations', 'laptop_model')) {
                    $table->longText('laptop_model')->nullable()->change();
                }
                if (Schema::hasColumn('product_variations', 'panel_number')) {
                    $table->longText('panel_number')->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'laptop_model')) {
                    $table->string('laptop_model', 255)->nullable()->change();
                    $table->index('laptop_model', 'products_laptop_model_index');
                }
                if (Schema::hasColumn('products', 'panel_number')) {
                    $table->string('panel_number', 255)->nullable()->change();
                    $table->index('panel_number', 'products_panel_number_index');
                }
                if (Schema::hasColumn('products', 'description')) {
                    $table->text('description')->nullable()->change();
                }
                if (Schema::hasColumn('products', 'short_description')) {
                    $table->text('short_description')->nullable()->change();
                }
                if (Schema::hasColumn('products', 'compatibility')) {
                    $table->text('compatibility')->nullable()->change();
                }
            });
        }

        if (Schema::hasTable('product_variations')) {
            Schema::table('product_variations', function (Blueprint $table) {
                if (Schema::hasColumn('product_variations', 'laptop_model')) {
                    $table->string('laptop_model', 255)->nullable()->change();
                    $table->index('laptop_model', 'product_variations_laptop_model_index');
                }
                if (Schema::hasColumn('product_variations', 'panel_number')) {
                    $table->string('panel_number', 255)->nullable()->change();
                    $table->index('panel_number', 'product_variations_panel_number_index');
                }
            });
        }
    }
};
