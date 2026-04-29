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
        Schema::table('u_o_m_s', function (Blueprint $table) {
            // Add type column after abbreviation for UOM categorization
            $table->enum('type', [
                'volume',      // ml, L, dl, etc.
                'weight',      // kg, g, mg, etc.
                'length',      // m, cm, mm, km, etc.
                'area',        // m², cm², etc.
                'count',       // pcs, dz, pack, box, ctn, bottle, can, pkt, bun, etc.
                'other'        // Generic fallback
            ])->default('other')->after('abbreviation');

            // Add index for faster filtering
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('u_o_m_s', function (Blueprint $table) {
            $table->dropIndex('u_o_m_s_type_index');
            $table->dropColumn('type');
        });
    }
};
