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
            $table->foreignId('ads_set_id')->constrained('ads_sets');
            $table->string('meta_ad_id');
            $table->string('name');
            $table->string('status');
            $table->string('creative_id');
            $table->string('creative_url');
            $table->string('thumbnail_url');
            $table->string('preview_url');
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
