<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('passport_document_path')->nullable()->after('passport_or_id_number');
            $table->string('drivers_license_document_path')->nullable()->after('drivers_license');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'passport_document_path',
                'drivers_license_document_path',
            ]);
        });
    }
};
