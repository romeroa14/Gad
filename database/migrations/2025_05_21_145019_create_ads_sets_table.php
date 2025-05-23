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
        Schema::create('ads_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ads_campaign_id')->constrained('ads_campaigns');
            $table->string('meta_adset_id');
            $table->string('name');
            $table->string('status');
            $table->string('target_spec');
            $table->string('billing_event');
            $table->float('daily_budget');
            $table->float('lifetime_budget');
            $table->json('meta_insights');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads_sets');
    }
};
