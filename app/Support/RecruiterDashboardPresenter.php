<?php

namespace App\Support;

use App\Enums\CourseLevel;
use App\Enums\PlacementStatus;
use App\Enums\Profession;
use App\Models\StudentProfile;

class RecruiterDashboardPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(): array
    {
        $open = StudentProfile::query()->listedForRecruiters()->registeredForPlacement()->where('placement_status', PlacementStatus::Open);

        $professions = [];
        foreach (Profession::cases() as $profession) {
            $professions[] = [
                'value' => $profession->value,
                'label' => $profession->label(),
                'count' => (clone $open)->whereJsonContains('professions', $profession->value)->count(),
            ];
        }

        $levels = [];
        foreach (CourseLevel::cases() as $level) {
            $levels[] = [
                'value' => $level->value,
                'label' => $level->label(),
                'count' => (clone $open)->matching(['level' => $level->value])->count(),
            ];
        }

        $recent = (clone $open)
            ->with('user.batchEnrollment.batch')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (StudentProfile $profile): array => RecruiterCandidatePresenter::card($profile))
            ->all();

        return [
            'stats' => [
                'total' => StudentProfile::query()->listedForRecruiters()->registeredForPlacement()->count(),
                'open' => (clone $open)->count(),
                'hired' => StudentProfile::query()->listedForRecruiters()->registeredForPlacement()->where('placement_status', PlacementStatus::Hired)->count(),
                'freshers' => (clone $open)->matching(['experience' => 'fresher'])->count(),
                'experienced' => (clone $open)->matching(['experience' => 'experienced'])->count(),
            ],
            'professions' => $professions,
            'levels' => $levels,
            'recent' => $recent,
        ];
    }
}
