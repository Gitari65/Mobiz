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
        Schema::create('tax_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "Standard VAT", "Zero-Rated"
            $table->enum('tax_type', ['VAT', 'Excise', 'Withholding', 'Other'])->default('VAT');
            $table->decimal('rate', 5, 2); // e.g., 16.00 for 16%
            $table->boolean('is_inclusive')->default(false); // Tax included in price or added on top
            $table->boolean('is_default')->default(false); // Default tax for new products
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('company_id');
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_configurations');
    }
};
