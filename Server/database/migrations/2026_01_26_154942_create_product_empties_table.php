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
        Schema::create('product_empties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('empty_product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity')->default(1); // How many empties per product unit
            $table->decimal('deposit_amount', 10, 2)->default(0); // Deposit charged for empties
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Prevent duplicate links
            $table->unique(['product_id', 'empty_product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_empties');
    }
};
