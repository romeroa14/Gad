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
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->foreignId('ads_set_id')->constrained('ads_sets');
            $table->string('meta_ad_id');
            $table->text('name');
            $table->string('status');
            $table->string('creative_id', 50)->nullable();
            $table->text('creative_url')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->text('preview_url')->nullable();
            $table->string('page_id')->nullable();
            $table->string('page_name')->nullable();
            $table->string('instagram_account_id')->nullable();
            $table->string('instagram_username')->nullable();
            $table->json('meta_insights');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
