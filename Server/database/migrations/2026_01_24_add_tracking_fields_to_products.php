<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Add company_id for multi-tenancy
            if (!Schema::hasColumn('products', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }
            
            // Add warehouse_id to track which warehouse the product belongs to
            if (!Schema::hasColumn('products', 'warehouse_id')) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('company_id');
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            }
            
            // Add created_by to track who added the product
            if (!Schema::hasColumn('products', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('description');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
            
            // Add updated_by to track who last edited the product
            if (!Schema::hasColumn('products', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            
            $table->dropColumn(['company_id', 'warehouse_id', 'created_by', 'updated_by']);
        });
    }
};
