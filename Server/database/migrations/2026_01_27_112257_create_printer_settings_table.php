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
        Schema::create('printer_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('header_message')->nullable();
            $table->string('footer_message')->nullable();
            $table->boolean('show_logo')->default(true);
            $table->boolean('show_taxes')->default(true);
            $table->boolean('show_discounts')->default(true);
            $table->enum('paper_size', ['58mm','80mm'])->default('58mm');
            $table->unsignedTinyInteger('font_size')->default(12);
            $table->enum('alignment', ['left','center','right'])->default('center');
            $table->unsignedTinyInteger('copies')->default(1);
            $table->timestamps();
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('printer_settings');
    }
};
