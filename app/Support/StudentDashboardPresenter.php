<?php

namespace App\Support;

use App\Enums\AssignmentSubmissionStatus;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\MaterialBatchAssignment;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\AttendanceSummaryService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class StudentDashboardPresenter
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
        $student->loadMissing([
            'studentProfile',
            'batchEnrollment.batch.center',
            'batchEnrollment.batch.teacher',
            'registrationPlan',
        ]);

        $profile = $student->studentProfile;
        $batch = $student->batchEnrollment?->batch;
        $trainer = $batch?->teacher;

        $visibleAssignments = $student->hasActiveRegistration()
            ? $this->assignments->visibleAssignmentsForStudent($student)
            : collect();

        $assignmentStats = $this->assignmentStats($visibleAssignments, $student->id);

        $notifications = $student->notifications()
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => StudentNotificationPresenter::format($notification))
            ->values()
            ->all();

        return [
            'profile' => $this->profileSummary($student, $profile),
            'batch' => $batch !== null ? $this->batchSummary($batch) : null,
            'trainer' => $trainer !== null ? $this->trainerSummary($trainer) : null,
            'registration' => [
                'expires_at' => $student->registration_expires_at?->toIso8601String(),
                'expires_at_label' => $student->registration_expires_at?->format('j M Y'),
                'plan_name' => $student->registrationPlan?->name,
                'days_remaining' => $student->registration_expires_at !== null
                    ? max(0, now()->startOfDay()->diffInDays($student->registration_expires_at->startOfDay(), false))
                    : null,
            ],
            'stats' => [
                'pending_assignments' => $assignmentStats['pending'],
                'completed_assignments' => $assignmentStats['completed'],
                'total_assignments' => $assignmentStats['total'],
                'study_materials_count' => self::studyMaterialsCount($student),
                'unread_notifications' => $student->unreadNotifications()->count(),
            ],
            'attendance' => $this->attendance->forStudent($student),
            'recent_assignments' => $this->recentAssignments($visibleAssignments),
            'notifications' => $notifications,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function profileSummary(User $student, ?StudentProfile $profile): array
    {
        return [
            'name' => $student->name,
            'email' => $student->email,
            'student_number' => $student->student_number,
            'headline' => $profile?->headline,
            'photo_url' => self::publicUrl($profile?->passport_photo_path),
            'completion_percent' => $this->profileCompletion($student, $profile),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function batchSummary(Batch $batch): array
    {
        return [
            'id' => $batch->id,
            'name' => $batch->name,
            'center_name' => $batch->center?->name,
            'language_label' => $batch->language->label(),
            'level_label' => $batch->level->label(),
            'is_active' => $batch->is_active,
            'starts_on_label' => $batch->starts_on?->format('j M Y'),
            'ends_on_label' => $batch->ends_on?->format('j M Y'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function trainerSummary(User $trainer): array
    {
        return [
            'id' => $trainer->id,
            'name' => $trainer->name,
            'email' => $trainer->email,
            'photo_url' => TrainerPresenter::photoUrl($trainer->avatar_path),
        ];
    }

    /**
     * @param  Collection<int, Assignment>  $assignments
     * @return array{pending: int, completed: int, total: int}
     */
    private function assignmentStats(Collection $assignments, int $studentId): array
    {
        $pending = 0;
        $completed = 0;

        foreach ($assignments as $assignment) {
            /** @var AssignmentSubmission|null $submission */
            $submission = $assignment->submissions->firstWhere('student_id', $studentId);

            if ($submission?->status === AssignmentSubmissionStatus::Graded) {
                $completed++;
            } else {
                $pending++;
            }
        }

        return [
            'pending' => $pending,
            'completed' => $completed,
            'total' => $assignments->count(),
        ];
    }

    /**
     * @param  Collection<int, Assignment>  $assignments
     * @return list<array<string, mixed>>
     */
    private function recentAssignments(Collection $assignments): array
    {
        return $assignments
            ->take(5)
            ->map(function (Assignment $assignment): array {
                $submission = $assignment->submissions->first();
                $data = AssignmentPresenter::assignment($assignment, false);
                $data['submission_status'] = $submission?->status->value;
                $data['submission_id'] = $submission?->id;

                return $data;
            })
            ->values()
            ->all();
    }

    private function profileCompletion(User $user, ?StudentProfile $profile): int
    {
        $checks = [
            filled($user->first_name),
            filled($user->last_name),
            filled($profile?->headline),
            filled($profile?->passport_photo_path),
            filled($profile?->introduction),
            filled($profile?->mobile_whatsapp),
            is_array($profile?->skills) && count($profile->skills) > 0,
            filled($profile?->current_address),
        ];

        $total = count($checks);
        $completed = collect($checks)->filter()->count();

        return $total > 0 ? (int) round(($completed / $total) * 100) : 0;
    }

    /**
     * @return array<string, mixed>
     */
    public static function studyMaterialsCount(User $student): int
    {
        $batchId = $student->batchEnrollment?->batch_id;

        if ($batchId === null) {
            return 0;
        }

        return MaterialBatchAssignment::query()
            ->where('batch_id', $batchId)
            ->count();
    }

    private static function publicUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return '/storage/'.ltrim(str_replace('\\', '/', $path), '/');
    }
}
