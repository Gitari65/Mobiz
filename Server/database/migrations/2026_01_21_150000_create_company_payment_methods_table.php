<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('company_payment_methods')) {
            Schema::create('company_payment_methods', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('payment_method_id');
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('cascade');
                $table->unique(['company_id', 'payment_method_id'], 'company_payment_method_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_payment_methods');
    }
};
