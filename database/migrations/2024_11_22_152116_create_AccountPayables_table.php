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
        Schema::create('AccountPayables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id')->nullable;
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->date('date_creation');
            $table->date('date_expiration');
            $table->enum('account_status', ['pending', 'paid', 'due'])->default('pendiente');;
            $table->integer('amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
