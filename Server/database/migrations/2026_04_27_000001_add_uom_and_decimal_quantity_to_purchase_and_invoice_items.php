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
        Schema::table('purchase_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_items', 'uom_id')) {
                $table->unsignedBigInteger('uom_id')->nullable()->after('product_id');
                $table->foreign('uom_id')->references('id')->on('u_o_m_s')->onDelete('set null');
            }

            $table->decimal('quantity', 12, 4)->change();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_items', 'uom_id')) {
                $table->unsignedBigInteger('uom_id')->nullable()->after('product_id');
                $table->foreign('uom_id')->references('id')->on('u_o_m_s')->onDelete('set null');
            }

            $table->decimal('quantity', 12, 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_items', 'uom_id')) {
                $table->dropForeign(['uom_id']);
                $table->dropColumn('uom_id');
            }

            $table->integer('quantity')->change();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_items', 'uom_id')) {
                $table->dropForeign(['uom_id']);
                $table->dropColumn('uom_id');
            }

            $table->integer('quantity')->change();
        });
    }
};