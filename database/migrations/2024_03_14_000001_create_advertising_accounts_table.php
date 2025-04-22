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
        Schema::create('advertising_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facebook_account_id')->constrained()->onDelete('cascade');
            $table->string('account_id'); // ID de la cuenta publicitaria en Facebook
            $table->string('name');
            $table->integer('status')->default(0);
            $table->string('currency', 10);
            $table->string('timezone', 100);
            $table->timestamps();
            
            // Índice para búsquedas rápidas por account_id
            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertising_accounts');
    }
}; 