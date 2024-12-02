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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('campaña_personalizada_id')->nullable();
            $table->foreign('campaña_personalizada_id')->references('id')->on('campañas_personalizadas')->onDelete('set null');
            
            $table->unsignedBigInteger('plane_id')->nullable();
            $table->foreign('plane_id')->references('id')->on('planes')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
