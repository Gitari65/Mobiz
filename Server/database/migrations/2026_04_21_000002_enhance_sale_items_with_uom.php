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
        Schema::table('sale_items', function (Blueprint $table) {
            // Add UoM tracking for sales
            $table->unsignedBigInteger('uom_id')->nullable()->after('product_id')->comment('UoM of the product at time of sale');
            $table->foreign('uom_id')->references('id')->on('u_o_m_s')->onDelete('set null');
            
            // Add decimal precision for fractional quantities
            $table->decimal('quantity', 12, 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['uom_id']);
            $table->dropColumn('uom_id');
            
            // Revert quantity to integer
            $table->integer('quantity')->change();
        });
    }
};
