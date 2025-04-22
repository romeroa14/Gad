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
        Schema::create('instagram_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('instagram_id')->unique()->comment('ID único de la cuenta en Instagram');
            $table->string('username')->unique();
            $table->string('name')->nullable();
            $table->text('biography')->nullable();
            $table->integer('followers_count')->default(0);
            $table->integer('follows_count')->default(0);
            $table->integer('media_count')->default(0);
            $table->string('profile_picture_url')->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_private')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->string('facebook_page_id')->nullable()->comment('ID de la página de Facebook conectada');
            $table->foreignId('advertising_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ad_account_id')->nullable()->comment('ID de la cuenta publicitaria en formato act_XXXXXX');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instagram_accounts');
    }
}; 