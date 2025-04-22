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
        Schema::create('facebook_pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_id')->unique()->comment('ID único de la página en Facebook');
            $table->string('name');
            $table->text('access_token')->nullable();
            $table->string('category')->nullable();
            $table->integer('followers_count')->default(0);
            $table->integer('likes_count')->default(0);
            $table->string('link')->nullable()->comment('URL de la página en Facebook');
            $table->string('picture_url')->nullable()->comment('URL de la imagen de perfil');
            $table->boolean('verification_status')->default(false);
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
        Schema::dropIfExists('facebook_pages');
    }
}; 