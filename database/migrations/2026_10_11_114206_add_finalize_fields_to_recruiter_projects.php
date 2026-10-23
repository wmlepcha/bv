<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruiter_projects', function (Blueprint $table) {
            $table->timestamp('finalized_at')->nullable();
            $table->json('requirement_template')->nullable();
            $table->timestamp('recruiter_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('recruiter_projects', function (Blueprint $table) {
            $table->dropColumn(['finalized_at', 'requirement_template', 'recruiter_updated_at']);
        });
    }
};
