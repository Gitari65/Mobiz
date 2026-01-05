<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // If table exists but has an unexpected user_id column, remove it so seeding works.
        if (Schema::hasTable('business_categories')) {
            if (Schema::hasColumn('business_categories', 'user_id')) {
                Schema::table('business_categories', function (Blueprint $table) {
                    // drop foreign key if present (safe) and then drop the column
                    try {
                        $table->dropForeign(['user_id']);
                    } catch (\Throwable $e) {
                        // ignore if FK doesn't exist
                    }
                    $table->dropColumn('user_id');
                });
            }
        }

        // Create table if it does not exist
        if (!Schema::hasTable('business_categories')) {
            Schema::create('business_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        // Seed default categories (safe to run multiple times)
        $now = now();
        $categories = [
            'Retail',
            'Restaurant',
            'Cafe',
            'Pharmacy',
            'Supermarket',
            'Gas Station',
            'Clothing',
            'Electronics',
            'Service',
            'Other',
        ];

        foreach ($categories as $cat) {
            DB::table('business_categories')->updateOrInsert(
                ['name' => $cat],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('business_categories');
    }
};
