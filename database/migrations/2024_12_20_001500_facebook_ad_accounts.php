<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('facebook_ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_id')->unique();
            $table->string('name');
            $table->string('currency');
            $table->string('timezone');
            $table->string('status');
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->index('account_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('facebook_ad_accounts');
    }
}; 