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
        Schema::table('companies', function (Blueprint $table) {

            // Business Details
            if (!Schema::hasColumn('companies', 'name')) {
                $table->string('name');
            }

            if (!Schema::hasColumn('companies', 'category')) {
                $table->string('category')->nullable();
            }

            if (!Schema::hasColumn('companies', 'email')) {
                $table->string('email')->unique();
            }

            if (!Schema::hasColumn('companies', 'phone')) {
                $table->string('phone')->nullable();
            }

            if (!Schema::hasColumn('companies', 'address')) {
                $table->string('address')->nullable();
            }

            // Owner Details
            if (!Schema::hasColumn('companies', 'owner_name')) {
                $table->string('owner_name')->nullable();
            }

            if (!Schema::hasColumn('companies', 'owner_position')) {
                $table->string('owner_position')->nullable();
            }

            // Status Fields
            if (!Schema::hasColumn('companies', 'approved')) {
                $table->boolean('approved')->default(false);
            }

            if (!Schema::hasColumn('companies', 'active')) {
                $table->boolean('active')->default(false);
            }

            // Optional Fields
            if (!Schema::hasColumn('companies', 'logo')) {
                $table->string('logo')->nullable();
            }

            if (!Schema::hasColumn('companies', 'website')) {
                $table->string('website')->nullable();
            }

            if (!Schema::hasColumn('companies', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'category',
                'phone',
                'address',
                'owner_name',
                'owner_position',
                'approved',
                'active',
                'logo',
                'website',
            ]);
        });
    }
};
