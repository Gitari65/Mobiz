<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('company_settings', 'mpesa_enabled')) {
                $table->boolean('mpesa_enabled')->default(false)->after('allow_credit_sales');
            }
            if (!Schema::hasColumn('company_settings', 'mpesa_type')) {
                $table->enum('mpesa_type', ['till', 'paybill'])->nullable()->after('mpesa_enabled');
            }
            if (!Schema::hasColumn('company_settings', 'mpesa_number')) {
                $table->string('mpesa_number')->nullable()->after('mpesa_type');
            }
            if (!Schema::hasColumn('company_settings', 'mpesa_business_name')) {
                $table->string('mpesa_business_name')->nullable()->after('mpesa_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['mpesa_enabled', 'mpesa_type', 'mpesa_number', 'mpesa_business_name']);
        });
    }
};
