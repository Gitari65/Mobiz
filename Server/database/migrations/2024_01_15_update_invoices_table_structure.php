<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoices')) {
            // Drop foreign key constraints first
            $this->dropForeignKeysIfExist();

            Schema::table('invoices', function (Blueprint $table) {
                // Make supplier_id and customer_id nullable if they exist
                if (Schema::hasColumn('invoices', 'supplier_id')) {
                    $table->unsignedBigInteger('supplier_id')->nullable()->change();
                }
                
                if (Schema::hasColumn('invoices', 'customer_id')) {
                    $table->unsignedBigInteger('customer_id')->nullable()->change();
                }
                
                // Add type column if missing
                if (!Schema::hasColumn('invoices', 'type')) {
                    $table->enum('type', ['purchase', 'sale', 'service', 'other'])
                        ->default('sale')
                        ->after('invoice_number');
                }
                
                // Add other missing columns if needed
                if (!Schema::hasColumn('invoices', 'invoice_date')) {
                    $table->date('invoice_date')->nullable()->after('type');
                }
                
                if (!Schema::hasColumn('invoices', 'subtotal')) {
                    $table->decimal('subtotal', 12, 2)->default(0)->after('due_date');
                }
                
                if (!Schema::hasColumn('invoices', 'balance')) {
                    $table->decimal('balance', 12, 2)->default(0)->after('paid_amount');
                }
            });

            // Re-add foreign key constraints with SET NULL
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreign('supplier_id')
                    ->references('id')
                    ->on('suppliers')
                    ->onDelete('set null');
                
                $table->foreign('customer_id')
                    ->references('id')
                    ->on('customers')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices')) {
            $this->dropForeignKeysIfExist();
            
            Schema::table('invoices', function (Blueprint $table) {
                if (Schema::hasColumn('invoices', 'supplier_id')) {
                    $table->unsignedBigInteger('supplier_id')->nullable(false)->change();
                }
                
                if (Schema::hasColumn('invoices', 'customer_id')) {
                    $table->unsignedBigInteger('customer_id')->nullable(false)->change();
                }
            });
        }
    }

    private function dropForeignKeysIfExist(): void
    {
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'invoices' 
            AND COLUMN_NAME IN ('supplier_id', 'customer_id')
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        foreach ($constraints as $constraint) {
            try {
                DB::statement("ALTER TABLE invoices DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
            } catch (\Exception $e) {
                // Constraint doesn't exist, continue
            }
        }
    }
};
