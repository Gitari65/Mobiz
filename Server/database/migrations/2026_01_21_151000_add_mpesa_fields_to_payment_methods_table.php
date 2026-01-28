<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_methods', 'mpesa_type')) {
                $table->string('mpesa_type')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('payment_methods', 'mpesa_number')) {
                $table->string('mpesa_number')->nullable()->after('mpesa_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['mpesa_type', 'mpesa_number']);
        });
    }
};
