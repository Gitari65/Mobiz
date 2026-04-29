<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('printer_settings', function (Blueprint $table) {
            $table->string('receipt_logo_path')->nullable()->after('show_logo');
            $table->string('invoice_title')->nullable()->after('copies');
            $table->string('invoice_subtitle')->nullable()->after('invoice_title');
            $table->string('invoice_footer_note')->nullable()->after('invoice_subtitle');
            $table->boolean('invoice_show_logo')->default(true)->after('invoice_footer_note');
        });
    }

    public function down(): void
    {
        Schema::table('printer_settings', function (Blueprint $table) {
            $table->dropColumn([
                'receipt_logo_path',
                'invoice_title',
                'invoice_subtitle',
                'invoice_footer_note',
                'invoice_show_logo',
            ]);
        });
    }
};
