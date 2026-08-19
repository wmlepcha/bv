<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->unique()->after('gateway_payment_id');
            $table->string('payment_mode')->nullable()->after('invoice_number');
            $table->string('payment_mode_detail')->nullable()->after('payment_mode');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->dropColumn(['invoice_number', 'payment_mode', 'payment_mode_detail']);
        });
    }
};

