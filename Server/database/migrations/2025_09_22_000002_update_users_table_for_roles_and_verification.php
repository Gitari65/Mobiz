<?php
// Migration to update users table for roles, verification, and audit
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('id');
            $table->boolean('verified')->default(false)->after('password');
            $table->unsignedBigInteger('created_by_user_id')->nullable()->after('verified');
            $table->foreign('role_id')->references('id')->on('roles');
            $table->foreign('created_by_user_id')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['created_by_user_id']);
            $table->dropColumn(['role_id', 'verified', 'created_by_user_id']);
        });
    }
};
