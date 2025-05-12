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
        Schema::create('fan_pages', function (Blueprint $table) {
            $table->id();
            $table->string('facebook_page_id')->unique(); // ID único de la página en Facebook
            $table->string('instagram_account_id')->unique(); // ID único de la cuenta en Instagram
            // $table->string('name'); // Nombre de la fanpage
            $table->string('category')->nullable(); // Categoría de la fanpage
            $table->string('picture_url')->nullable(); // URL de la imagen de perfil
            $table->foreignId('client_id')->constrained()->onDelete('cascade'); // Relación con el cliente
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fan_pages');
    }
};
