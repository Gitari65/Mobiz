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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Promotion type: percentage, fixed_amount, buy_x_get_y, spend_save, bulk_discount
            $table->string('type', 50);
            
            // Discount values
            $table->decimal('discount_value', 10, 2)->nullable(); // For percentage or fixed amount
            $table->integer('buy_quantity')->nullable(); // For buy X get Y
            $table->integer('get_quantity')->nullable(); // For buy X get Y
            $table->decimal('minimum_purchase', 10, 2)->nullable(); // Minimum spend
            $table->integer('minimum_quantity')->nullable(); // Minimum items
            
            // Application scope: all, category, product, customer_group
            $table->string('scope', 50)->default('all');
            $table->json('scope_items')->nullable(); // Product IDs, Category names, or Customer group IDs
            
            // Customer targeting
            $table->boolean('first_time_only')->default(false);
            $table->json('customer_groups')->nullable(); // Array of customer types/groups
            
            // Time constraints
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            
            // Usage limits
            $table->integer('usage_limit_total')->nullable(); // Total uses allowed
            $table->integer('usage_limit_per_customer')->nullable();
            $table->integer('usage_count')->default(0); // Track total uses
            
            // Status and priority
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Higher number = higher priority
            $table->boolean('is_stackable')->default(false); // Can combine with other promotions
            
            // Company relationship
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            
            // Tracking
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            
            $table->timestamps();
        });

        // Track individual promotion usage
        Schema::create('promotion_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promotion_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->decimal('discount_amount', 10, 2);
            $table->timestamp('used_at');
            
            $table->foreign('promotion_id')->references('id')->on('promotions')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_usage');
        Schema::dropIfExists('promotions');
    }
};
