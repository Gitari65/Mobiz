<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'mpesa_transaction_id')) {
                $table->foreignId('mpesa_transaction_id')->nullable()->after('balance_due')->constrained('mpesa_transactions')->nullOnDelete();
            }
            if (! Schema::hasColumn('sales', 'mpesa_phone_number')) {
                $table->string('mpesa_phone_number', 20)->nullable()->after('mpesa_transaction_id');
            }
            if (! Schema::hasColumn('sales', 'mpesa_checkout_request_id')) {
                $table->string('mpesa_checkout_request_id')->nullable()->after('mpesa_phone_number');
            }
            if (! Schema::hasColumn('sales', 'mpesa_receipt_number')) {
                $table->string('mpesa_receipt_number')->nullable()->after('mpesa_checkout_request_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            foreach (['mpesa_receipt_number', 'mpesa_checkout_request_id', 'mpesa_phone_number'] as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('sales', 'mpesa_transaction_id')) {
                $table->dropConstrainedForeignId('mpesa_transaction_id');
            }
        });
    }
};