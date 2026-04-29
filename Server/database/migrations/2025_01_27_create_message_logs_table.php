<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_template_id')->nullable()->constrained('message_templates')->cascadeOnDelete();
            $table->foreignId('sent_by_user_id')->constrained('users')->cascadeOnDelete();
            
            $table->enum('type', ['sms', 'email'])->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_contact'); // phone or email
            $table->string('recipient_type')->default('customer'); // customer, supplier, staff
            
            $table->longText('subject')->nullable();
            $table->longText('body');
            
            // Status tracking
            $table->enum('status', ['pending', 'sent', 'failed', 'delivered'])->default('pending');
            $table->string('external_id')->nullable(); // Twilio SID or email service ID
            $table->text('error_message')->nullable();
            
            // Campaign tracking
            $table->string('campaign_name')->nullable();
            $table->string('campaign_type')->nullable(); // 'weekly_reminder', 'promotional', 'invoice', etc.
            
            $table->json('metadata')->nullable(); // Additional context: sale_id, customer_id, etc.
            $table->integer('retry_count')->default(0);
            
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'created_at']);
            $table->index(['recipient_contact', 'type']);
            $table->index('external_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_logs');
    }
};
