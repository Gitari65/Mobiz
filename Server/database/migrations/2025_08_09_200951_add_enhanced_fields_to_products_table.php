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
        Schema::table('products', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('products', 'sku')) {
                $table->string('sku', 100)->unique()->nullable()->after('name');
            }
            if (!Schema::hasColumn('products', 'category')) {
                $table->string('category', 100)->nullable()->after('price');
            }
            if (!Schema::hasColumn('products', 'brand')) {
                $table->string('brand', 100)->nullable()->after('category');
            }
            if (!Schema::hasColumn('products', 'cost_price')) {
                $table->decimal('cost_price', 10, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('products', 'low_stock_threshold')) {
                $table->integer('low_stock_threshold')->nullable()->after('stock_quantity');
            }
            if (!Schema::hasColumn('products', 'description')) {
                $table->text('description')->nullable()->after('low_stock_threshold');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'sku',
                'category', 
                'brand',
                'cost_price',
                'low_stock_threshold',
                'description'
            ]);
        });
    }
};
