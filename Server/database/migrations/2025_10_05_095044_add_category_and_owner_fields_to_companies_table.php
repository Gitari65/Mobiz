<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'category')) {
                $table->string('category')->nullable()->after('name');
            }
            if (!Schema::hasColumn('companies', 'owner_name')) {
                $table->string('owner_name')->nullable()->after('category');
            }
            if (!Schema::hasColumn('companies', 'owner_position')) {
                $table->string('owner_position')->nullable()->after('owner_name');
            }
            if (!Schema::hasColumn('companies', 'active')) {
                $table->boolean('active')->default(false)->after('approved');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['category', 'owner_name', 'owner_position', 'active']);
        });
    }
};
