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
        Schema::create('price_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., Retail, Stockist, Superstockist
            $table->string('code', 50)->unique(); // e.g., retail, stockist, superstockist
            $table->text('description')->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0); // Discount from base price
            $table->boolean('is_system')->default(false); // System price groups cannot be deleted
            $table->unsignedBigInteger('company_id')->nullable(); // Null for system groups
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_groups');
    }
};
