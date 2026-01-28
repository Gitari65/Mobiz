<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'category')) $table->string('category')->nullable();
            if (!Schema::hasColumn('companies', 'phone')) $table->string('phone')->nullable();
            if (!Schema::hasColumn('companies', 'kra_pin')) $table->string('kra_pin')->nullable();
            if (!Schema::hasColumn('companies', 'address')) $table->string('address')->nullable();
            if (!Schema::hasColumn('companies', 'city')) $table->string('city')->nullable();
            if (!Schema::hasColumn('companies', 'county')) $table->string('county')->nullable();
            if (!Schema::hasColumn('companies', 'zip_code')) $table->string('zip_code')->nullable();
            if (!Schema::hasColumn('companies', 'country')) $table->string('country')->nullable();
            if (!Schema::hasColumn('companies', 'owner_name')) $table->string('owner_name')->nullable();
            if (!Schema::hasColumn('companies', 'owner_position')) $table->string('owner_position')->nullable();
            if (!Schema::hasColumn('companies', 'approved')) $table->boolean('approved')->default(false);
        });
    }
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'category', 'phone', 'kra_pin', 'address', 'city', 'county', 'zip_code', 'country', 'owner_name', 'owner_position', 'approved'
            ]);
        });
    }
};
