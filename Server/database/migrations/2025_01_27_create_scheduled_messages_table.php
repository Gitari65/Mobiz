<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_template_id')->constrained('message_templates')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            
            $table->string('name');
            $table->enum('frequency', ['once', 'daily', 'weekly', 'monthly'])->default('once');
            $table->json('schedule_config'); // { day: 'Monday', time: '10:00', etc. }
            
            // Targeting
            $table->json('recipient_filters')->nullable(); // { min_spent: 1000, status: 'active', etc. }
            $table->integer('estimated_recipients')->default(0);
            
            $table->boolean('is_active')->default(true);
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_send_at')->nullable();
            
            $table->integer('total_sent')->default(0);
            $table->integer('successful_sends')->default(0);
            $table->integer('failed_sends')->default(0);
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'is_active']);
            $table->index('next_send_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_messages');
    }
};
