<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone_number', 20);
            $table->decimal('amount', 12, 2);
            $table->string('reference')->nullable();
            $table->string('description')->nullable();
            $table->string('merchant_request_id')->nullable()->index();
            $table->string('checkout_request_id')->nullable()->unique();
            $table->string('status')->default('pending')->index();
            $table->string('result_code')->nullable();
            $table->text('result_desc')->nullable();
            $table->string('mpesa_receipt_number')->nullable()->index();
            $table->dateTime('transaction_date')->nullable();
            $table->json('raw_callback')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_transactions');
    }
};