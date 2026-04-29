<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            if (!Schema::hasColumn('product_prices', 'uom_id')) {
                $table->unsignedBigInteger('uom_id')->nullable()->after('price_group_id');
                $table->foreign('uom_id')->references('id')->on('u_o_m_s')->onDelete('cascade');
            }

            $table->unsignedBigInteger('price_group_id')->nullable()->change();
            $table->unique(['product_id', 'uom_id']);
        });
    }

    public function down(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'uom_id']);

            if (Schema::hasColumn('product_prices', 'uom_id')) {
                $table->dropForeign(['uom_id']);
                $table->dropColumn('uom_id');
            }

            $table->unsignedBigInteger('price_group_id')->nullable(false)->change();
        });
    }
};