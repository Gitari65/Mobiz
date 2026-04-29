<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('status');
            }
            if (!Schema::hasColumn('invoices', 'mpesa_receipt_number')) {
                $table->string('mpesa_receipt_number')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('invoices', 'mpesa_phone_number')) {
                $table->string('mpesa_phone_number', 20)->nullable()->after('mpesa_receipt_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            foreach (['mpesa_phone_number', 'mpesa_receipt_number', 'payment_method'] as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};