<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruiter_project_candidates', function (Blueprint $table) {
            $table->timestamp('congratulated_at')->nullable();
            $table->timestamp('requirements_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('recruiter_project_candidates', function (Blueprint $table) {
            $table->dropColumn(['congratulated_at', 'requirements_sent_at']);
        });
    }
};
