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
        Schema::table('purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id')->nullable()->after('id');
            $table->string('invoice_number', 100)->nullable()->after('supplier_name');
            $table->date('invoice_date')->nullable()->after('invoice_number');
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('invoice_date');
            $table->decimal('subtotal', 12, 2)->default(0)->after('warehouse_id');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('subtotal');
            $table->decimal('shipping_cost', 12, 2)->default(0)->after('tax_amount');
            $table->decimal('discount', 12, 2)->default(0)->after('shipping_cost');
            
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('total_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn([
                'supplier_id',
                'invoice_number',
                'invoice_date',
                'warehouse_id',
                'subtotal',
                'tax_amount',
                'shipping_cost',
                'discount'
            ]);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
