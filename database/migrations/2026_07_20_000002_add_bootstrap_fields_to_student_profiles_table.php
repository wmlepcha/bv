<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('mobile_whatsapp')->nullable()->after('user_id');
            $table->date('date_of_birth')->nullable()->after('mobile_whatsapp');
            $table->string('place_of_birth')->nullable()->after('date_of_birth');
            $table->string('civil_status')->nullable()->after('place_of_birth');
            $table->string('gender')->nullable()->after('civil_status');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'mobile_whatsapp',
                'date_of_birth',
                'place_of_birth',
                'civil_status',
                'gender',
            ]);
        });
    }
};
