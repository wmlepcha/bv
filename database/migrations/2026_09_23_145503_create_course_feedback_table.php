<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('center_id')->nullable()->constrained('centers')->nullOnDelete();
            $table->string('level');
            $table->unsignedTinyInteger('overall_satisfaction');
            $table->unsignedTinyInteger('trainer_quality');
            $table->unsignedTinyInteger('explanation_clarity');
            $table->unsignedTinyInteger('class_engagement');
            $table->unsignedTinyInteger('materials_usefulness');
            $table->unsignedTinyInteger('skills_improvement');
            $table->unsignedTinyInteger('confidence');
            $table->string('most_improved_skill');
            $table->string('needs_improvement_skill');
            $table->string('pace');
            $table->unsignedTinyInteger('support_satisfaction');
            $table->text('liked_most');
            $table->text('improvement_suggestion');
            $table->timestamps();

            $table->unique(['student_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_feedback');
    }
};
