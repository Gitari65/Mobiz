<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('price_groups', 'is_enabled')) {
                $table->boolean('is_enabled')->default(true)->after('is_system');
            }
        });

        Schema::table('price_groups', function (Blueprint $table) {
            $table->dropUnique('price_groups_code_unique');
            $table->unique(['company_id', 'code'], 'price_groups_company_id_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('price_groups', function (Blueprint $table) {
            $table->dropUnique('price_groups_company_id_code_unique');
            $table->unique('code', 'price_groups_code_unique');
        });

        Schema::table('price_groups', function (Blueprint $table) {
            if (Schema::hasColumn('price_groups', 'is_enabled')) {
                $table->dropColumn('is_enabled');
            }
        });
    }
};