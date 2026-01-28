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
        Schema::table('users', function (Blueprint $table) {
            $table->integer('otp_resend_count')->default(0)->after('password');
            $table->timestamp('last_otp_request_at')->nullable()->after('otp_resend_count');
            $table->timestamp('last_otp_verified_at')->nullable()->after('last_otp_request_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['otp_resend_count', 'last_otp_request_at', 'last_otp_verified_at']);
        });
    }
};
