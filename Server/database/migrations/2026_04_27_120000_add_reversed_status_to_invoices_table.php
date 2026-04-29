<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Keep legacy statuses and add reversed.
        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','sent','paid','overdue','cancelled','reversed') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Convert reversed to cancelled before removing enum value.
        DB::statement("UPDATE invoices SET status = 'cancelled' WHERE status = 'reversed'");
        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','sent','paid','overdue','cancelled') NOT NULL DEFAULT 'draft'");
    }
};
