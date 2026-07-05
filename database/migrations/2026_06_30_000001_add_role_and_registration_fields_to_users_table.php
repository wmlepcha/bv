<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->after('password');
            $table->string('student_number')->nullable()->unique()->after('role');
            $table->timestamp('registration_paid_at')->nullable()->after('student_number');
            $table->string('registration_fee_paid_via')->nullable()->after('registration_paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'student_number',
                'registration_paid_at',
                'registration_fee_paid_via',
            ]);
        });
    }
};
