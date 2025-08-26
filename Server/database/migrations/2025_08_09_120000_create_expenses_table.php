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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('category')->index();
            $table->string('subcategory')->nullable();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->decimal('tax_amount', 8, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->enum('payment_method', [
                'cash', 
                'bank_transfer', 
                'credit_card', 
                'debit_card', 
                'mobile_money', 
                'cheque', 
                'online_payment'
            ])->default('cash');
            $table->string('vendor_name')->nullable();
            $table->string('receipt_number')->nullable();
            $table->date('expense_date');
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->text('notes')->nullable();
            $table->string('receipt_image')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            // Recurring expense fields
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_frequency', ['weekly', 'monthly', 'quarterly', 'yearly'])->nullable();
            $table->date('next_due_date')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['category', 'expense_date']);
            $table->index(['status', 'expense_date']);
            $table->index(['user_id', 'expense_date']);
            $table->index('expense_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
