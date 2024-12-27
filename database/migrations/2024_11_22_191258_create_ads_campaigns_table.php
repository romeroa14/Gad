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
        Schema::create('ads_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('plan');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('budget', 15, 2);
            $table->decimal('actual_cost', 15, 2)->nullable();
            $table->string('status'); // Activa, Pausada, Finalizada
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads_campaigns');
    }
};
