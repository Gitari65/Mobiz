<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('business_categories', 'user_id')) {
            Schema::table('business_categories', function (Blueprint $table) {
                $table->dropForeignKey(['user_id']); // Drop foreign key if exists
                $table->dropColumn('user_id');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('business_categories', 'user_id')) {
            Schema::table('business_categories', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id');
            });
        }
    }
};
