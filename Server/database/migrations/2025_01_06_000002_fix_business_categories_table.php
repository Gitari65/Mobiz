<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Drop existing table if it exists
        Schema::dropIfExists('business_categories');

        // Recreate with correct structure
        Schema::create('business_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Seed default categories
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
            DB::table('business_categories')->insert([
                'name' => $cat,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('business_categories');
    }
};
