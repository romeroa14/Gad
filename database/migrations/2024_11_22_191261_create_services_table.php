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
            // $table->id();
            // $table->unsignedBigInteger('personalized_id')->nullable();
            // $table->foreign('personalized_id')->references('id')->on('personalizeds')->onDelete('set null');

            // $table->unsignedBigInteger('plan_id')->nullable();
            // $table->foreign('plan_id')->references('id')->on('plans')->onDelete('set null');
            // $table->timestamps();

            $table->id();
            $table->morphs('serviceable'); // Crea serviceable_id y serviceable_type
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->nullable();
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
