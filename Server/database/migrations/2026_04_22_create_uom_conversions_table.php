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
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_uom_id')->constrained('u_o_m_s');
            $table->foreignId('to_uom_id')->constrained('u_o_m_s');
            $table->decimal('conversion_factor', 12, 6); // e.g., 1 kg = 1000 g (1000.000000)
            $table->string('description')->nullable();
            $table->timestamps();
            
            // Ensure no duplicate conversions
            $table->unique(['from_uom_id', 'to_uom_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
