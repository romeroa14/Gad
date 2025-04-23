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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('business');
            $table->foreignId('country_id')->constrained('countries');
            $table->foreignId('state_id')->constrained('states');
            $table->text('address')->nullable();
            $table->string('facebook_page_id')->nullable()->after('address');
            $table->string('instagram_account_id')->nullable()->after('facebook_page_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['facebook_page_id', 'instagram_account_id']);
        });
    }
};
