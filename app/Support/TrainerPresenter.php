<?php

namespace App\Support;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentSubmissionStatus;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\MaterialAssignment;
use App\Models\User;

class TrainerPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function forIndex(User $trainer): array
    {
        return [
            'id' => $trainer->id,
            'name' => $trainer->name,
            'email' => $trainer->email,
            'photo_url' => self::photoUrl($trainer->avatar_path),
            'batches_count' => $trainer->teaching_batches_count ?? 0,
            'students_count' => (int) ($trainer->students_count ?? 0),
            'assignments_count' => $trainer->created_assignments_count ?? 0,
            'batches' => $trainer->relationLoaded('teachingBatches')
                ? $trainer->teachingBatches
                    ->map(fn (Batch $batch): array => [
                        'id' => $batch->id,
                        'name' => $batch->name,
                        'ends_on_label' => $batch->ends_on?->format('j M Y'),
                        'is_active' => $batch->is_active,
                    ])
                    ->values()
                    ->all()
                : [],
            'created_at' => $trainer->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function show(User $trainer): array
    {
        $batchIds = $trainer->teachingBatches()->pluck('id');

        $studentsCount = $batchIds->isEmpty()
            ? 0
            : BatchEnrollment::query()
                ->whereIn('batch_id', $batchIds)
                ->distinct('student_id')
                ->count('student_id');

        $totalAssignments = Assignment::query()
            ->where('created_by', $trainer->id)
            ->count();

        $publishedAssignments = Assignment::query()
            ->where('created_by', $trainer->id)
            ->where('status', AssignmentStatus::Published)
            ->count();

        $assignmentIds = Assignment::query()
            ->where('created_by', $trainer->id)
            ->pluck('id');

        $submissionsCount = $assignmentIds->isEmpty()
            ? 0
            : AssignmentSubmission::query()
                ->whereIn('assignment_id', $assignmentIds)
                ->count();

        $gradedSubmissionsCount = $assignmentIds->isEmpty()
            ? 0
            : AssignmentSubmission::query()
                ->whereIn('assignment_id', $assignmentIds)
                ->where('status', AssignmentSubmissionStatus::Graded)
                ->count();

        $sharedMaterialsCount = MaterialAssignment::query()
            ->where('trainer_id', $trainer->id)
            ->count();

        $batches = $trainer->teachingBatches()
            ->with(['center:id,name'])
            ->withCount(['enrollments', 'assignments'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn (Batch $batch): array => [
                'id' => $batch->id,
                'name' => $batch->name,
                'center_name' => $batch->center?->name,
                'language_label' => $batch->language->label(),
                'level_label' => $batch->level->label(),
                'is_active' => $batch->is_active,
                'starts_on_label' => $batch->starts_on?->format('j M Y'),
                'ends_on_label' => $batch->ends_on?->format('j M Y'),
                'enrollments_count' => $batch->enrollments_count,
                'assignments_count' => $batch->assignments_count,
            ])
            ->values()
            ->all();

        $assignments = Assignment::query()
            ->where('created_by', $trainer->id)
            ->with(['batch:id,name'])
            ->withCount([
                'submissions',
                'submissions as graded_submissions_count' => fn ($query) => $query
                    ->where('status', AssignmentSubmissionStatus::Graded),
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Assignment $assignment): array => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'batch_id' => $assignment->batch_id,
                'batch_name' => $assignment->batch?->name ?? '—',
                'status' => $assignment->status->value,
                'status_label' => $assignment->status->label(),
                'submissions_count' => $assignment->submissions_count,
                'graded_submissions_count' => $assignment->graded_submissions_count,
                'due_at' => $assignment->due_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        $students = $batchIds->isEmpty()
            ? []
            : BatchEnrollment::query()
                ->whereIn('batch_id', $batchIds)
                ->with([
                    'student:id,name,email,student_number',
                    'batch:id,name',
                ])
                ->orderByDesc('created_at')
                ->get()
                ->unique('student_id')
                ->values()
                ->map(fn (BatchEnrollment $enrollment): array => [
                    'id' => $enrollment->student_id,
                    'name' => $enrollment->student?->name ?? '—',
                    'email' => $enrollment->student?->email ?? '—',
                    'student_number' => $enrollment->student?->student_number,
                    'batch_name' => $enrollment->batch?->name ?? '—',
                    'enrolled_at' => $enrollment->created_at?->toIso8601String(),
                ])
                ->all();

        return [
            'trainer' => [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'email' => $trainer->email,
                'photo_url' => self::photoUrl($trainer->avatar_path),
                'created_at' => $trainer->created_at?->toIso8601String(),
                'created_at_label' => $trainer->created_at?->format('j M Y'),
            ],
            'stats' => [
                'batches_count' => count($batches),
                'students_count' => $studentsCount,
                'total_assignments' => $totalAssignments,
                'published_assignments' => $publishedAssignments,
                'submissions_count' => $submissionsCount,
                'graded_submissions_count' => $gradedSubmissionsCount,
                'shared_materials_count' => $sharedMaterialsCount,
            ],
            'batches' => $batches,
            'assignments' => $assignments,
            'students' => $students,
            'remarks' => [],
        ];
    }

    public static function photoUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return '/storage/'.ltrim(str_replace('\\', '/', $path), '/');
    }
}
