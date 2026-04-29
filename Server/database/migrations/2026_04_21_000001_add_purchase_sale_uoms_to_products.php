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
            // Add purchase UoM (e.g., 50L)
            $table->unsignedBigInteger('purchase_uom_id')->nullable()->after('uom_id')->comment('UoM for purchasing');
            $table->foreign('purchase_uom_id')->references('id')->on('u_o_m_s')->onDelete('set null');
            
            // Add sale UoM (e.g., 1L, 250ml)
            $table->unsignedBigInteger('sale_uom_id')->nullable()->after('purchase_uom_id')->comment('UoM for selling');
            $table->foreign('sale_uom_id')->references('id')->on('u_o_m_s')->onDelete('set null');
            
            // Conversion ratio: how many sale units = 1 purchase unit
            // Example: 1 purchase unit (50L) = 50 sale units (1L each)
            $table->decimal('conversion_ratio', 10, 4)->default(1)->after('sale_uom_id')->comment('Sale units per purchase unit');
            
            // Track stock in base unit (purchase unit)
            $table->boolean('track_by_purchase_unit')->default(true)->after('conversion_ratio')->comment('If true, stock_quantity is in purchase units');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['purchase_uom_id']);
            $table->dropForeign(['sale_uom_id']);
            $table->dropColumn(['purchase_uom_id', 'sale_uom_id', 'conversion_ratio', 'track_by_purchase_unit']);
        });
    }
};
