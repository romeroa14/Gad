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
        Schema::create('account_payables', function (Blueprint $table) {
            $table->id();
            // $table->unsignedBigInteger('supplier_id')->nullable;
            $table->foreignId('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreignId('finance_id')->references('id')->on('finances')->onDelete('cascade');
            $table->date('date_creation');
            $table->date('date_expiration');
            $table->enum('account_status', ['pending', 'paid', 'due'])->default('pendiente');;
            $table->decimal('amount', 15, 2);
            $table->string('status');
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
