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
        // Create invoices table only if it doesn't exist
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number')->unique();
                $table->enum('type', ['purchase', 'sale', 'service', 'other'])->default('sale');
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->date('invoice_date');
                $table->date('due_date')->nullable();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->decimal('balance', 12, 2)->default(0);
                $table->enum('status', ['draft', 'sent', 'paid', 'cancelled'])->default('draft');
                $table->text('notes')->nullable();
                $table->timestamps();
                
                // Foreign keys
                $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                
                // Indexes
                $table->index('invoice_number');
                $table->index('type');
                $table->index('company_id');
                $table->index('supplier_id');
                $table->index('customer_id');
                $table->index('user_id');
                $table->index('invoice_date');
                $table->index('status');
            });
        }

        // Create invoice_items table only if it doesn't exist
        if (!Schema::hasTable('invoice_items')) {
            Schema::create('invoice_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('invoice_id');
                $table->unsignedBigInteger('product_id')->nullable();
                $table->string('description')->nullable();
                $table->integer('quantity');
                $table->decimal('unit_price', 12, 2);
                $table->decimal('total_price', 12, 2);
                $table->timestamps();
                
                // Foreign keys
                $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
                
                // Indexes
                $table->index('invoice_id');
                $table->index('product_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
