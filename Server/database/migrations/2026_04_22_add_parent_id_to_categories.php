<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add parent_id to business_categories
        if (Schema::hasTable('business_categories')) {
            Schema::table('business_categories', function (Blueprint $table) {
                if (!Schema::hasColumn('business_categories', 'parent_id')) {
                    $table->unsignedBigInteger('parent_id')->nullable()->after('name');
                    $table->foreign('parent_id')
                        ->references('id')
                        ->on('business_categories')
                        ->onDelete('cascade');
                }
                if (!Schema::hasColumn('business_categories', 'description')) {
                    $table->text('description')->nullable()->after('parent_id');
                }
                if (!Schema::hasColumn('business_categories', 'icon')) {
                    $table->string('icon')->nullable()->after('description');
                }
                if (!Schema::hasColumn('business_categories', 'display_order')) {
                    $table->integer('display_order')->default(0)->after('icon');
                }
            });
        }

        // Add parent_id to product_categories
        if (Schema::hasTable('product_categories')) {
            Schema::table('product_categories', function (Blueprint $table) {
                if (!Schema::hasColumn('product_categories', 'parent_id')) {
                    $table->unsignedBigInteger('parent_id')->nullable()->after('name');
                    $table->foreign('parent_id')
                        ->references('id')
                        ->on('product_categories')
                        ->onDelete('cascade');
                }
                if (!Schema::hasColumn('product_categories', 'display_order')) {
                    $table->integer('display_order')->default(0)->after('icon');
                }
            });
        }
    }

    public function down(): void
    {
        // Rollback product_categories
        if (Schema::hasTable('product_categories')) {
            Schema::table('product_categories', function (Blueprint $table) {
                if (Schema::hasColumn('product_categories', 'parent_id')) {
                    $table->dropForeign(['parent_id']);
                    $table->dropColumn('parent_id');
                }
                if (Schema::hasColumn('product_categories', 'display_order')) {
                    $table->dropColumn('display_order');
                }
            });
        }

        // Rollback business_categories
        if (Schema::hasTable('business_categories')) {
            Schema::table('business_categories', function (Blueprint $table) {
                if (Schema::hasColumn('business_categories', 'parent_id')) {
                    $table->dropForeign(['parent_id']);
                    $table->dropColumn('parent_id');
                }
                if (Schema::hasColumn('business_categories', 'description')) {
                    $table->dropColumn('description');
                }
                if (Schema::hasColumn('business_categories', 'icon')) {
                    $table->dropColumn('icon');
                }
                if (Schema::hasColumn('business_categories', 'display_order')) {
                    $table->dropColumn('display_order');
                }
            });
        }
    }
};
