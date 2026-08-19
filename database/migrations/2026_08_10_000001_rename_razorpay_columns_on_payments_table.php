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
            if (Schema::hasColumn('payments', 'razorpay_order_id') && ! Schema::hasColumn('payments', 'gateway_order_id')) {
                $table->renameColumn('razorpay_order_id', 'gateway_order_id');
            }

            if (Schema::hasColumn('payments', 'razorpay_payment_id') && ! Schema::hasColumn('payments', 'gateway_payment_id')) {
                $table->renameColumn('razorpay_payment_id', 'gateway_payment_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'gateway_order_id') && ! Schema::hasColumn('payments', 'razorpay_order_id')) {
                $table->renameColumn('gateway_order_id', 'razorpay_order_id');
            }

            if (Schema::hasColumn('payments', 'gateway_payment_id') && ! Schema::hasColumn('payments', 'razorpay_payment_id')) {
                $table->renameColumn('gateway_payment_id', 'razorpay_payment_id');
            }
        });
    }
};
