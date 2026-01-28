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
            // Drop the old global unique constraint
            try {
                $table->dropUnique('products_sku_unique');
            } catch (\Throwable $e) {
                // Constraint might not exist
            }
            
            // Create compound unique constraint: SKU should be unique per company and warehouse
            $table->unique(['company_id', 'warehouse_id', 'sku'], 'products_company_warehouse_sku_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop the compound unique constraint
            try {
                $table->dropUnique('products_company_warehouse_sku_unique');
            } catch (\Throwable $e) {
                // Constraint might not exist
            }
            
            // Restore the original global unique constraint
            $table->unique('sku', 'products_sku_unique');
        });
    }
};
