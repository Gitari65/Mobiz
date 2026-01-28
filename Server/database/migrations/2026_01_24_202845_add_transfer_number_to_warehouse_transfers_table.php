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
        Schema::table('warehouse_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouse_transfers', 'transfer_number')) {
                $table->string('transfer_number', 50)->unique()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouse_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('warehouse_transfers', 'transfer_number')) {
                $table->dropColumn('transfer_number');
            }
        });
    }
};
