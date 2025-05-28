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
            $table->text('name');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->foreignId('advertising_account_id')->nullable()->constrained('advertising_accounts')->onDelete('set null');
            $table->string('meta_campaign_id', 50)->nullable()->index();
            
            // Información básica de la campaña
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('budget', 15, 2)->nullable()->default(0);
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
