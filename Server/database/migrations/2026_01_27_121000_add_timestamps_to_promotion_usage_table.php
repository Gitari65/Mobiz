<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('promotion_usage', function (Blueprint $table) {
            $table->timestamps();
        });

        // Backfill existing rows to avoid null timestamp issues
        DB::table('promotion_usage')->update([
            'created_at' => DB::raw('COALESCE(used_at, NOW())'),
            'updated_at' => DB::raw('COALESCE(used_at, NOW())'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotion_usage', function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
    }
};
