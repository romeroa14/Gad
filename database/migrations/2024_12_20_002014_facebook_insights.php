<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('facebook_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facebook_campaign_id')
                ->constrained('facebook_campaigns')
                ->onDelete('cascade');
            $table->date('date');
            $table->bigInteger('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('reach')->default(0);
            $table->decimal('spend', 10, 2)->default(0);
            $table->decimal('cpc', 10, 2)->nullable();
            $table->decimal('cpm', 10, 2)->nullable();
            $table->decimal('ctr', 8, 4)->nullable();
            $table->decimal('frequency', 8, 2)->nullable();
            $table->integer('unique_clicks')->default(0);
            $table->decimal('unique_ctr', 8, 4)->nullable();
            $table->decimal('cost_per_unique_click', 10, 2)->nullable();
            $table->integer('conversions')->default(0);
            $table->decimal('cost_per_conversion', 10, 2)->nullable();
            $table->decimal('conversion_rate', 8, 4)->nullable();
            $table->integer('social_reach')->default(0);
            $table->integer('social_impressions')->default(0);
            $table->json('actions')->nullable();
            $table->integer('website_purchases')->default(0);
            $table->decimal('return_on_ad_spend', 10, 2)->nullable();
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index('date');
            $table->index(['facebook_campaign_id', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('facebook_insights');
    }
};
