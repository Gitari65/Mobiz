<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id'); // Add the column
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade'); // Add foreign key constraint
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropForeign(['company_id']); // Drop foreign key
            $table->dropColumn('company_id'); // Drop the column
        });
    }
};