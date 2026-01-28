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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('tax_configuration_id')->nullable()->after('price')->constrained()->onDelete('set null');
            $table->enum('tax_category', ['standard', 'zero-rated', 'exempt'])->default('standard')->after('tax_configuration_id');
            $table->decimal('tax_rate', 5, 2)->default(16.00)->after('tax_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['tax_configuration_id']);
            $table->dropColumn(['tax_configuration_id', 'tax_category', 'tax_rate']);
        });
    }
};
