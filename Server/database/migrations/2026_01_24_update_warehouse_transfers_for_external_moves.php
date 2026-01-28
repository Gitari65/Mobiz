<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_transfers', function (Blueprint $table) {
            // Remove existing foreign to allow nullability change
            try {
                $table->dropForeign(['to_warehouse_id']);
            } catch (\Throwable $e) {
                // Ignore if constraint name differs or doesn't exist
            }
        });

        // Change to_warehouse_id to nullable via raw SQL to avoid DBAL dependency
        DB::statement('ALTER TABLE warehouse_transfers MODIFY to_warehouse_id BIGINT UNSIGNED NULL');

        Schema::table('warehouse_transfers', function (Blueprint $table) {
            // Optional company tracking
            if (!Schema::hasColumn('warehouse_transfers', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }

            // Additional transfer metadata
            if (!Schema::hasColumn('warehouse_transfers', 'transfer_type')) {
                $table->string('transfer_type', 50)->default('warehouse');
            }
            if (!Schema::hasColumn('warehouse_transfers', 'reason')) {
                $table->string('reason', 255)->nullable();
            }
            if (!Schema::hasColumn('warehouse_transfers', 'reference')) {
                $table->string('reference', 255)->nullable();
            }
            if (!Schema::hasColumn('warehouse_transfers', 'external_target')) {
                $table->string('external_target', 255)->nullable();
            }
            if (!Schema::hasColumn('warehouse_transfers', 'note')) {
                $table->text('note')->nullable();
            }

            // Re-add foreign key for to_warehouse_id as nullable
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_transfers', function (Blueprint $table) {
            try {
                $table->dropForeign(['company_id']);
            } catch (\Throwable $e) {}
            try {
                $table->dropForeign(['to_warehouse_id']);
            } catch (\Throwable $e) {}

            $table->dropColumn([
                'company_id',
                'transfer_type',
                'reason',
                'reference',
                'external_target',
                'note',
            ]);
        });

        // Restore to_warehouse_id to NOT NULL
        DB::statement('ALTER TABLE warehouse_transfers MODIFY to_warehouse_id BIGINT UNSIGNED NOT NULL');

        Schema::table('warehouse_transfers', function (Blueprint $table) {
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses');
        });
    }
};
