<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBxgyConfigToPromotionsTable extends Migration
{
    public function up()
    {
        Schema::table('promotions', function (Blueprint $table) {
            // Add bxgy_config column if it doesn't exist
            if (!Schema::hasColumn('promotions', 'bxgy_config')) {
                $table->json('bxgy_config')->nullable()->after('get_products');
            }
        });
    }

    public function down()
    {
        Schema::table('promotions', function (Blueprint $table) {
            if (Schema::hasColumn('promotions', 'bxgy_config')) {
                $table->dropColumn('bxgy_config');
            }
        });
    }
}
