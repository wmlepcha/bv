<?php

namespace App\Support;

use App\Enums\AssignmentSubmissionStatus;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\CourseFeedback;
use App\Models\LearningItem;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\AttendanceSummaryService;

class LanguageProgressPresenter
{
    public function __construct(
        private readonly AssignmentService $assignments,
        private readonly AttendanceSummaryService $attendance,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(User $student): array
    {
        $student->loadMissing('batchEnrollment.batch.center', 'batchEnrollment.batch.teacher');

        $enrollment = $student->batchEnrollment;
        $batch = $enrollment?->batch;

        $items = $this->assignments->visibleAssignmentsForStudent($student)
            ->map(fn (Assignment $assignment): array => $this->assignmentRow($assignment, $student))
            ->values();

        $graded = $items->where('status', 'graded');
        $maxTotal = $graded->sum('max_score');

        return [
            'course' => $batch === null ? null : [
                'language_label' => $batch->language->label(),
                'level' => $batch->level->value,
                'batch_name' => $batch->name,
                'center_name' => $batch->center?->name,
                'trainer_name' => $batch->teacher?->name,
                'starts_on' => $batch->starts_on?->toDateString(),
                'ends_on' => $batch->ends_on?->toDateString(),
                'enrolled_on' => $enrollment->created_at?->toDateString(),
                'elapsed_percent' => $this->elapsedPercent($batch->starts_on, $batch->ends_on),
            ],
            'assignments' => [
                'total' => $items->count(),
                'graded' => $graded->count(),
                'submitted' => $items->where('status', 'submitted')->count(),
                'pending' => $items->whereIn('status', ['pending', 'overdue'])->count(),
                'overdue' => $items->where('status', 'overdue')->count(),
                'average_percent' => $maxTotal > 0 ? (int) round($graded->sum('score') / $maxTotal * 100) : null,
                'items' => $items->all(),
            ],
            'attendance' => $this->attendance->forStudent($student),
            'self_assessment' => $this->selfAssessment($student),
            'study_bank_saved' => LearningItem::query()->where('user_id', $student->id)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function assignmentRow(Assignment $assignment, User $student): array
    {
        /** @var AssignmentSubmission|null $submission */
        $submission = $assignment->submissions->first();

        $status = match (true) {
            $submission?->status === AssignmentSubmissionStatus::Graded => 'graded',
            $submission !== null => 'submitted',
            $assignment->due_at?->isPast() === true => 'overdue',
            default => 'pending',
        };

        $score = $status === 'graded' ? $submission->final_score : null;

        return [
            'id' => $assignment->id,
            'title' => $assignment->title,
            'due_at' => $assignment->due_at?->toDateString(),
            'status' => $status,
            'score' => $score,
            'max_score' => $assignment->max_score,
            'percent' => $score !== null && $assignment->max_score > 0
                ? (int) round($score / $assignment->max_score * 100)
                : null,
        ];
    }

    private function elapsedPercent(?\DateTimeInterface $start, ?\DateTimeInterface $end): ?int
    {
        if ($start === null || $end === null || $end <= $start) {
            return null;
        }

        $elapsed = (now()->getTimestamp() - $start->getTimestamp()) / ($end->getTimestamp() - $start->getTimestamp());

        return (int) round(max(0, min(1, $elapsed)) * 100);
    }

    /**
     * Skills the student reported in their latest course feedback form.
     *
     * @return array<string, mixed>|null
     */
    private function selfAssessment(User $student): ?array
    {
        $feedback = CourseFeedback::query()->where('student_id', $student->id)->latest('form_number')->first();

        if ($feedback === null) {
            return null;
        }

        return [
            'level' => $feedback->level->value,
            'confidence' => $feedback->confidence,
            'most_improved' => $feedback->most_improved_skill->label(),
            'needs_improvement' => $feedback->needs_improvement_skill->label(),
        ];
    }
}
