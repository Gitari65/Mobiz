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
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('expense_categories')->onDelete('cascade');
            $table->string('color', 7)->default('#6B7280'); // Hex color code
            $table->string('icon')->default('fas fa-tag'); // FontAwesome icon class
            $table->boolean('is_active')->default(true);
            $table->decimal('budget_limit', 12, 2)->nullable();
            $table->decimal('alert_threshold', 5, 2)->default(80); // Percentage
            $table->timestamps();
            
            // Indexes
            $table->index('parent_id');
            $table->index('is_active');
            $table->unique(['name', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
