<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['sms', 'email', 'both'])->default('both');
            $table->enum('category', ['promotional', 'transactional', 'notification', 'reminder'])->default('transactional');
            
            // Email specific
            $table->string('email_subject')->nullable();
            $table->longText('email_body')->nullable();
            
            // SMS specific
            $table->longText('sms_body')->nullable();
            
            // Available variables for template
            $table->json('variables')->nullable(); // ['customer_name', 'sale_amount', 'product_name', etc.]
            
            // Configuration
            $table->boolean('is_active')->default(true);
            $table->enum('recipient_type', ['customers', 'suppliers', 'staff'])->default('customers');
            $table->text('description')->nullable();
            
            $table->timestamps();
            
            $table->index(['company_id', 'category']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
