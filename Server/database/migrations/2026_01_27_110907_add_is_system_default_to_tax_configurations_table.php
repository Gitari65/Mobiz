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
        Schema::table('tax_configurations', function (Blueprint $table) {
            $table->boolean('is_system_default')->default(false)->after('is_active')->comment('Marks Kenya default tax configs that only super users can edit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_configurations', function (Blueprint $table) {
            $table->dropColumn('is_system_default');
        });
    }
};
