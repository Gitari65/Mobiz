<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Ensure roles exist (safe to run multiple times)
        $now = now();
        DB::table('roles')->updateOrInsert(
            ['name' => 'Superuser'],
            ['description' => null, 'created_at' => $now, 'updated_at' => $now]
        );
        DB::table('roles')->updateOrInsert(
            ['name' => 'Admin'],
            ['description' => null, 'created_at' => $now, 'updated_at' => $now]
        );
        DB::table('roles')->updateOrInsert(
            ['name' => 'Cashier'],
            ['description' => null, 'created_at' => $now, 'updated_at' => $now]
        );
    }

    public function down(): void
    {
        DB::table('roles')->whereIn('name', ['Superuser', 'Admin', 'Cashier'])->delete();
    }
};
