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
        // Primero eliminamos la tabla existente si hay problemas con ella
        Schema::dropIfExists('ads_campaigns');
        
        // Creamos la tabla con la estructura básica necesaria
        Schema::create('ads_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade')->nullable();
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade')->nullable();
            $table->foreignId('advertising_account_id')->nullable()->constrained('advertising_accounts')->onDelete('set null');
            $table->string('meta_campaign_id')->nullable()->index();
            
            // Información básica de la campaña
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('budget', 15, 2)->default(0);
            $table->decimal('actual_cost', 15, 2)->nullable();
            $table->decimal('cost_per_conversion', 15, 2)->nullable();
            $table->string('status');
            
            // Campos para seguimiento y datos adicionales
            $table->timestamp('last_synced_at')->nullable();
            $table->json('meta_insights')->nullable(); // Para datos adicionales de Facebook si son necesarios
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
