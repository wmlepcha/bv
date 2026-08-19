<?php

namespace App\Support;

use App\Models\StudentProfile;
use App\Models\User;

class CvProfilePresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function present(User $user, ?StudentProfile $profile = null): array
    {
        $profile ??= $user->studentProfile;
        $vitae = VitaeData::normalize($profile?->vitae_data);

        $skills = $profile?->skills;
        if (! is_array($skills)) {
            $skills = [];
        }

        return [
            'candidate_id' => $user->student_number,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => $user->name,
            'email' => $user->email,
            'state_code' => $user->state_code,
            'state_name' => $user->state_code
                ? (IndianStates::codeToNameMap()[$user->state_code] ?? $user->state_code)
                : null,
            'special_id' => $user->special_id?->value,
            'special_id_label' => $user->special_id?->label(),
            'registration_year' => $user->registration_year,
            'mobile_whatsapp' => $profile?->mobile_whatsapp,
            'date_of_birth' => $profile?->date_of_birth?->format('Y-m-d'),
            'place_of_birth' => $profile?->place_of_birth,
            'civil_status' => $profile?->civil_status?->value,
            'gender' => $profile?->gender?->value,
            'secondary_email' => $profile?->secondary_email,
            'secondary_mobile' => $profile?->secondary_mobile,
            'current_address' => $profile?->current_address,
            'permanent_address' => $profile?->permanent_address,
            'nationality' => $profile?->nationality,
            'passport_or_id_number' => $profile?->passport_or_id_number,
            'passport_document_url' => self::publicUrl($profile?->passport_document_path),
            'passport_document_path' => $profile?->passport_document_path,
            'drivers_license' => $profile?->drivers_license,
            'drivers_license_document_url' => self::publicUrl($profile?->drivers_license_document_path),
            'drivers_license_document_path' => $profile?->drivers_license_document_path,
            'passport_photo_url' => self::publicUrl($profile?->passport_photo_path),
            'full_body_photo_url' => self::publicUrl($profile?->full_body_photo_path),
            'passport_photo_path' => $profile?->passport_photo_path,
            'full_body_photo_path' => $profile?->full_body_photo_path,
            'introduction' => $profile?->introduction,
            'hobbies' => $profile?->hobbies,
            'skills' => array_values($skills),
            'gap_reasons' => $profile?->gap_reasons,
            'current_status' => $profile?->current_status,
            'pursuing_details' => $profile?->pursuing_details,
            'headline' => $profile?->headline,
            'languages' => $profile?->languages ?? [],
            'proficiency_summary' => $profile?->proficiency_summary,
            'vitae' => $vitae,
        ];
    }

    private static function publicUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        // Relative URL so the browser uses the current host (Herd / artisan serve / production)
        // instead of APP_URL, which can break passport photos in preview.
        return '/storage/'.ltrim(str_replace('\\', '/', $path), '/');
    }
}
