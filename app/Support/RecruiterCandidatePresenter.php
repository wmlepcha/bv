<?php

namespace App\Support;

use App\Enums\PlacementStatus;
use App\Enums\Profession;
use App\Models\StudentProfile;
use App\Models\User;

class RecruiterCandidatePresenter
{
    /**
     * Card-level summary; safe to show for hired candidates.
     * Hired cards are locked in lists; pass $honorApproval inside a recruiter's own project
     * to keep students BV approved for that recruiter openable.
     *
     * @return array<string, mixed>
     */
    public static function card(StudentProfile $profile, ?User $viewer = null, bool $honorApproval = false): array
    {
        $viewer ??= auth()->user();
        $status = $profile->placement_status ?? PlacementStatus::Open;
        $level = $profile->user->batchEnrollment?->batch?->level;

        return [
            'id' => $profile->id,
            'name' => $profile->user->name,
            'student_number' => $profile->user->student_number,
            'headline' => $profile->headline,
            'photo_url' => self::publicStorageUrl(
                $profile->passport_photo_path ?: $profile->full_body_photo_path,
            ),
            'experience' => data_get($profile->vitae_data, 'is_newbie') === true ? 'fresher' : 'experienced',
            'language_level' => $level?->value,
            'viewable' => $viewer === null || $profile->isFullyViewableBy($viewer, $honorApproval),
            'placement_status' => $status->value,
            'placement_label' => $status->label(),
            'professions' => collect($profile->professions ?? [])
                ->map(fn (string $value): ?string => Profession::tryFrom($value)?->label())
                ->filter()
                ->values()
                ->all(),
        ];
    }

    public static function publicStorageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return '/storage/'.ltrim(str_replace('\\', '/', $path), '/');
    }
}
