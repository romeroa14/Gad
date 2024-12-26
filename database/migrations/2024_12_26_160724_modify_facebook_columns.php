<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('facebook_access_token', 1500)->nullable()->change();
            $table->string('facebook_id', 100)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('facebook_access_token', 255)->nullable()->change();
            $table->string('facebook_id', 255)->nullable()->change();
        });
    }
}; 