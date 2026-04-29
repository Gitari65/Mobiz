<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->string('review_status')->default('open')->after('notes');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('review_status');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');

            $table->index('company_id');
            $table->index('review_status');
            $table->index('reviewed_by');
        });

        // Backfill company_id from users when possible for existing logs.
        DB::statement('UPDATE audit_logs al JOIN users u ON u.id = al.user_id SET al.company_id = u.company_id WHERE al.company_id IS NULL');
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['review_status']);
            $table->dropIndex(['reviewed_by']);
            $table->dropColumn(['company_id', 'review_status', 'reviewed_by', 'reviewed_at']);
        });
    }
};
