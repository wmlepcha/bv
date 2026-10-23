<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruiter_project_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruiter_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['recruiter_project_id', 'student_profile_id'], 'project_candidate_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruiter_project_candidates');
    }
};
