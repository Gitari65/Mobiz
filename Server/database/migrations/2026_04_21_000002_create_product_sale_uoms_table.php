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
        Schema::create('product_sale_uoms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('uom_id')->constrained('u_o_m_s')->onDelete('cascade');
            $table->decimal('conversion_ratio', 10, 4)->default(1)->comment('How many of this UOM make 1 purchase unit');
            $table->boolean('is_default')->default(false)->comment('Default UOM to show in cart');
            $table->timestamps();
            
            $table->unique(['product_id', 'uom_id']);
            $table->index('product_id');
            $table->index('uom_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_sale_uoms');
    }
};
