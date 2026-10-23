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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->string('referrer_name', 100);
            $table->string('referrer_phone', 20);
            $table->string('referred_name', 100);
            $table->string('referred_phone', 20);
            $table->string('referred_email')->nullable();
            $table->string('interest');
            $table->string('preferred_country');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('referred_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
