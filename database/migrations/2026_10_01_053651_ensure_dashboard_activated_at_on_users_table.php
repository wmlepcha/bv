<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Production recorded 2026_09_23_170351 as run without this column; add it if missing.
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'dashboard_activated_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('dashboard_activated_at')->nullable()->after('registration_paid_at');
        });

        DB::table('users')
            ->where('role', 'student')
            ->whereNotNull('registration_paid_at')
            ->update(['dashboard_activated_at' => DB::raw('registration_paid_at')]);
    }

    public function down(): void {}
};
