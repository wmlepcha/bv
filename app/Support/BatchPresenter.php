<?php

namespace App\Support;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentSubmissionStatus;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\MaterialBatchAssignment;

class BatchPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function forIndex(Batch $batch): array
    {
        return [
            'id' => $batch->id,
            'name' => $batch->name,
            'center_name' => $batch->center?->name,
            'language' => $batch->language->value,
            'language_label' => $batch->language->label(),
            'level' => $batch->level->value,
            'teacher_name' => $batch->teacher?->name,
            'teacher_id' => $batch->teacher_id,
            'is_active' => $batch->is_active,
            'starts_on' => $batch->starts_on?->format('Y-m-d'),
            'ends_on' => $batch->ends_on?->format('Y-m-d'),
            'starts_on_label' => $batch->starts_on?->format('j M Y'),
            'ends_on_label' => $batch->ends_on?->format('j M Y'),
            'enrollments_count' => $batch->enrollments_count ?? 0,
            'setup_percent' => self::setupChecklist($batch)['percent'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function show(Batch $batch): array
    {
        $batch->loadMissing(['center:id,name', 'teacher:id,name,email']);
        $batch->loadCount('enrollments');

        $assignmentIds = Assignment::query()
            ->where('batch_id', $batch->id)
            ->pluck('id');

        $totalAssignments = $assignmentIds->count();
        $publishedAssignments = Assignment::query()
            ->where('batch_id', $batch->id)
            ->where('status', AssignmentStatus::Published)
            ->count();

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

        $sharedMaterialsCount = MaterialBatchAssignment::query()
            ->where('batch_id', $batch->id)
            ->count();

        $setup = self::setupChecklist($batch);
        $batchAssignments = Assignment::query()
            ->where('batch_id', $batch->id)
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
                'status' => $assignment->status->value,
                'status_label' => $assignment->status->label(),
                'submissions_count' => $assignment->submissions_count,
                'graded_submissions_count' => $assignment->graded_submissions_count,
                'due_at' => $assignment->due_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        $students = BatchEnrollment::query()
            ->where('batch_id', $batch->id)
            ->with(['student:id,name,email,student_number'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (BatchEnrollment $enrollment): array => [
                'id' => $enrollment->student_id,
                'name' => $enrollment->student?->name ?? '—',
                'email' => $enrollment->student?->email ?? '—',
                'student_number' => $enrollment->student?->student_number,
                'enrolled_at' => $enrollment->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        $submissions = $assignmentIds->isEmpty()
            ? []
            : AssignmentSubmission::query()
                ->whereIn('assignment_id', $assignmentIds)
                ->with([
                    'assignment:id,title',
                    'student:id,name',
                ])
                ->orderByDesc('submitted_at')
                ->get()
                ->map(fn (AssignmentSubmission $submission): array => [
                    'id' => $submission->id,
                    'assignment_id' => $submission->assignment_id,
                    'assignment_title' => $submission->assignment?->title ?? '—',
                    'student_name' => $submission->student?->name ?? '—',
                    'status' => $submission->status->value,
                    'status_label' => $submission->status === AssignmentSubmissionStatus::Graded
                        ? 'Graded'
                        : 'Submitted',
                    'submitted_at' => $submission->submitted_at?->toIso8601String(),
                    'final_score' => $submission->final_score,
                ])
                ->values()
                ->all();

        return [
            'batch' => [
                'id' => $batch->id,
                'name' => $batch->name,
                'center_name' => $batch->center?->name,
                'language' => $batch->language->value,
                'language_label' => $batch->language->label(),
                'level' => $batch->level->value,
                'level_label' => $batch->level->label(),
                'teacher_name' => $batch->teacher?->name,
                'teacher_email' => $batch->teacher?->email,
                'is_active' => $batch->is_active,
                'starts_on' => $batch->starts_on?->format('Y-m-d'),
                'ends_on' => $batch->ends_on?->format('Y-m-d'),
                'starts_on_label' => $batch->starts_on?->format('j M Y'),
                'ends_on_label' => $batch->ends_on?->format('j M Y'),
            ],
            'stats' => [
                'enrollments_count' => $batch->enrollments_count,
                'total_assignments' => $totalAssignments,
                'published_assignments' => $publishedAssignments,
                'submissions_count' => $submissionsCount,
                'graded_submissions_count' => $gradedSubmissionsCount,
                'shared_materials_count' => $sharedMaterialsCount,
                'setup_percent' => $setup['percent'],
            ],
            'setup' => $setup,
            'assignments' => $batchAssignments,
            'students' => $students,
            'submissions' => $submissions,
        ];
    }

    /**
     * @return array{
     *     percent: int,
     *     completed: int,
     *     total: int,
     *     items: list<array{key: string, label: string, complete: bool}>
     * }
     */
    private static function setupChecklist(Batch $batch): array
    {
        $items = [
            [
                'key' => 'center',
                'label' => 'Training center assigned',
                'complete' => $batch->center_id !== null,
            ],
            [
                'key' => 'trainer',
                'label' => 'Trainer assigned',
                'complete' => $batch->teacher_id !== null,
            ],
            [
                'key' => 'starts_on',
                'label' => 'Start date set',
                'complete' => $batch->starts_on !== null,
            ],
            [
                'key' => 'ends_on',
                'label' => 'End date set',
                'complete' => $batch->ends_on !== null,
            ],
            [
                'key' => 'students',
                'label' => 'Students enrolled',
                'complete' => ($batch->enrollments_count ?? 0) > 0,
            ],
        ];

        $completed = collect($items)->where('complete', true)->count();
        $total = count($items);

        return [
            'percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
            'completed' => $completed,
            'total' => $total,
            'items' => $items,
        ];
    }
}
