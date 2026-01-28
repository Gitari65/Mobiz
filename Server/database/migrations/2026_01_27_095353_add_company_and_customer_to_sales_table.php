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
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->after('company_id')->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->after('customer_id')->constrained()->onDelete('set null');
            $table->string('payment_method')->nullable()->after('user_id');
            $table->decimal('discount', 10, 2)->default(0)->after('payment_method');
            $table->decimal('tax', 10, 2)->default(0)->after('discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['company_id', 'customer_id', 'user_id', 'payment_method', 'discount', 'tax']);
        });
    }
};
