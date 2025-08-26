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
            // SKU/Barcode field
            $table->string('sku')->nullable()->unique()->after('name');
            
            // Category field
            $table->string('category')->nullable()->after('sku');
            
            // Brand field
            $table->string('brand')->nullable()->after('category');
            
            // Cost Price field
            $table->decimal('cost_price', 10, 2)->nullable()->after('brand');
            
            // Low Stock Alert threshold
            $table->integer('low_stock_threshold')->default(5)->after('stock_quantity');
            
            // Product Description
            $table->text('description')->nullable()->after('low_stock_threshold');
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
