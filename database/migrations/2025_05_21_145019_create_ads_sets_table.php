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
            $table->string('meta_adset_id', 50);
            $table->text('name');
            $table->string('status');
            $table->string('optimization_goal')->nullable()->after('target_spec');
            $table->json('target_spec')->nullable();
            $table->string('billing_event')->nullable();
            $table->float('daily_budget')->nullable();
            $table->float('lifetime_budget')->nullable();
            $table->json('meta_insights');
            $table->string('page_id')->nullable()->after('meta_insights');
            $table->string('page_name')->nullable()->after('page_id');
            $table->string('instagram_account_id')->nullable()->after('page_name');
            $table->string('instagram_username')->nullable()->after('instagram_account_id');
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
