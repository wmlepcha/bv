<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('secondary_email')->nullable()->after('gender');
            $table->string('secondary_mobile')->nullable()->after('secondary_email');
            $table->text('current_address')->nullable()->after('secondary_mobile');
            $table->text('permanent_address')->nullable()->after('current_address');
            $table->string('nationality')->nullable()->after('permanent_address');
            $table->string('passport_or_id_number')->nullable()->after('nationality');
            $table->string('drivers_license')->nullable()->after('passport_or_id_number');
            $table->string('passport_photo_path')->nullable()->after('drivers_license');
            $table->string('full_body_photo_path')->nullable()->after('passport_photo_path');
            $table->text('introduction')->nullable()->after('full_body_photo_path');
            $table->text('hobbies')->nullable()->after('introduction');
            $table->json('skills')->nullable()->after('hobbies');
            $table->text('gap_reasons')->nullable()->after('skills');
            $table->string('current_status')->nullable()->after('gap_reasons');
            $table->text('pursuing_details')->nullable()->after('current_status');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'secondary_email',
                'secondary_mobile',
                'current_address',
                'permanent_address',
                'nationality',
                'passport_or_id_number',
                'drivers_license',
                'passport_photo_path',
                'full_body_photo_path',
                'introduction',
                'hobbies',
                'skills',
                'gap_reasons',
                'current_status',
                'pursuing_details',
            ]);
        });
    }
};
