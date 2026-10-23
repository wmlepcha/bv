<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->string('label', 10);
            $table->string('title')->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('assignment_questions', function (Blueprint $table) {
            $table->foreignId('section_id')
                ->nullable()
                ->after('assignment_id')
                ->constrained('assignment_sections')
                ->nullOnDelete();
        });

        $assignmentsWithQuestions = DB::table('assignment_questions')
            ->select('assignment_id')
            ->distinct()
            ->pluck('assignment_id');

        foreach ($assignmentsWithQuestions as $assignmentId) {
            $sectionId = DB::table('assignment_sections')->insertGetId([
                'assignment_id' => $assignmentId,
                'label' => 'General',
                'title' => null,
                'instructions' => null,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('assignment_questions')
                ->where('assignment_id', $assignmentId)
                ->whereNull('section_id')
                ->update(['section_id' => $sectionId]);
        }
    }

    public function down(): void
    {
        Schema::table('assignment_questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('section_id');
        });

        Schema::dropIfExists('assignment_sections');
    }
};
