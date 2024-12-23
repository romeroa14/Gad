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
    Schema::create('facebook_tokens', function (Blueprint $table) {
        $table->id();
        $table->text('EAAIkrOlnGSABOz59q6ZBDNkwgk2nIh9olHwXVgr6b0Bn6wBKZAy8VUH89JVCOxvSgcAEQIYESwxVSY0uPG0HgrofQto4dLMgasv1qLurf4vReQdwx9bF5oLaNjZBlXgAlf5Wnj2TTMz352EqjPDlZCJr5Swu1XGoajcnlUpxgVZAVliFHdFlmxdctfuiVJfaIVToYZCZC3D33gIAXgjRYi8ZC3IL2fpGo7ykfVAoqB9x');
        $table->timestamp('expires_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facebook_tokens');
    }
};
