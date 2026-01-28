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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'price_group_id')) {
                $table->unsignedBigInteger('price_group_id')->nullable()->after('company_id');
                $table->foreign('price_group_id')->references('id')->on('price_groups')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'price_group_id')) {
                $table->dropForeign(['price_group_id']);
                $table->dropColumn('price_group_id');
            }
        });
    }
};
