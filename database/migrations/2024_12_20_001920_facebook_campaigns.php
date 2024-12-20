<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('facebook_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_id')->unique();
            $table->foreignId('facebook_ad_account_id')
                  ->constrained('facebook_ad_accounts')
                  ->onDelete('cascade');
            $table->string('name');
            $table->string('status');
            $table->string('objective');
            $table->decimal('spend', 10, 2)->default(0);
            $table->timestamp('start_time')->nullable();
            $table->timestamp('stop_time')->nullable();
            $table->timestamps();

            $table->index('campaign_id');
            $table->index(['facebook_ad_account_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('facebook_campaigns');
    }
};