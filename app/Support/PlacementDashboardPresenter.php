<?php

namespace App\Support;

use App\Enums\PlacementStatus;
use App\Enums\RegistrationPurpose;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;

class PlacementDashboardPresenter
{
    public function __construct(private readonly LanguageProgressPresenter $progress) {}

    /**
     * @return array{onboarded: int, placed: int, pending: int, requests: int}
     */
    public function counts(): array
    {
        $onboarded = StudentProfile::query()->registeredForPlacement()->count();
        $placed = StudentProfile::query()->registeredForPlacement()->where('placement_status', PlacementStatus::Hired)->count();

        return [
            'onboarded' => $onboarded,
            'placed' => $placed,
            'pending' => $onboarded - $placed,
            'requests' => StudentProfile::query()
                ->where('registration_purpose', RegistrationPurpose::Training)
                ->whereNotNull('placement_requested_at')
                ->count(),
        ];
    }

    /**
     * One row per placement student: profile completion, assignment average and attendance, each 0-100 or null.
     *
     * @param  Collection<int, StudentProfile>  $profiles
     * @return array<int, array<string, mixed>>
     */
    public function heatmapRows(Collection $profiles): array
    {
        return $profiles->map(function (StudentProfile $profile): array {
            $progress = $this->progress->present($profile->user);

            return [
                'id' => $profile->id,
                'name' => $profile->user->name,
                'student_number' => $profile->user->student_number,
                'batch' => $progress['course']['batch_name'] ?? null,
                'placement_status' => $profile->placement_status->value,
                'profile' => $this->profileCompletion($profile),
                'assignments' => $progress['assignments']['average_percent'],
                'submission_rate' => $this->submissionRate($progress['assignments']),
                'attendance' => $progress['attendance']['percentage'],
            ];
        })->values()->all();
    }

    public function profileCompletion(StudentProfile $profile): int
    {
        $vitae = $profile->vitae_data ?? [];

        $checks = [
            filled($profile->headline),
            filled($profile->introduction),
            filled($profile->mobile_whatsapp),
            $profile->date_of_birth !== null,
            filled($profile->nationality),
            filled($profile->current_address),
            filled($profile->passport_photo_path),
            filled($profile->passport_document_path),
            ! empty($profile->skills),
            ! empty($vitae['bilingual'] ?? []),
            ! empty($vitae['qualifications'] ?? []) || ! empty($vitae['experiences'] ?? []),
        ];

        return (int) round(count(array_filter($checks)) / count($checks) * 100);
    }

    /**
     * @param  array{total: int, graded: int, submitted: int}  $assignments
     */
    private function submissionRate(array $assignments): ?int
    {
        return $assignments['total'] > 0
            ? (int) round(($assignments['graded'] + $assignments['submitted']) / $assignments['total'] * 100)
            : null;
    }
}
