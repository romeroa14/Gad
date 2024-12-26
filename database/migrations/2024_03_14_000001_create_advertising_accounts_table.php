<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertising_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('account_id');
            $table->string('name');
            $table->integer('status');
            $table->string('currency');
            $table->string('timezone');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertising_accounts');
    }
}; 