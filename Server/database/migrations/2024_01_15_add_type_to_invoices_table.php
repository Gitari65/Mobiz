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
        // Add type column to existing invoices table
        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'type')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->enum('type', ['purchase', 'sale', 'service', 'other'])
                    ->default('sale')
                    ->after('invoice_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'type')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
