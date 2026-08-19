<?php

namespace App\Support;

use App\Models\EducationLevel;
use App\Models\Inquiry;
use Illuminate\Support\Facades\Storage;

class InquiryPresenter
{
    /**
     * @var array<string, string>
     */
    private const LABELS = [
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'date_of_birth' => 'Date of birth',
        'email' => 'Email',
        'phone' => 'Phone',
        'address' => 'Address',
        'city' => 'City',
        'country' => 'Country',
        'education_level' => 'Education level',
        'field_of_study' => 'Field of study',
        'years_of_experience' => 'Years of experience',
        'languages' => 'Languages spoken',
        'career_goals' => 'Career goals',
        'qualification_certificate' => 'Qualification certificate',
        'resume' => 'Resume',
        'id_document' => 'ID document',
        'company_name' => 'Company name',
        'industry' => 'Industry',
        'company_size' => 'Company size',
        'website' => 'Website',
        'contact_name' => 'Contact name',
        'designation' => 'Designation',
        'roles_hiring' => 'Roles hiring for',
        'positions_count' => 'Number of open positions',
        'required_skills' => 'Required skills',
        'preferred_locations' => 'Preferred locations',
        'heard_about' => 'How did you hear about us?',
        'hiring_timeline' => 'Hiring timeline',
        'additional_notes' => 'Additional notes',
    ];

    /**
     * @var array<string, array<string, string>>
     */
    private const OPTIONS = [
        'years_of_experience' => [
            '0-1' => '0–1 years',
            '1-3' => '1–3 years',
            '3-5' => '3–5 years',
            '5+' => '5+ years',
        ],
        'company_size' => [
            '1-10' => '1–10 employees',
            '11-50' => '11–50 employees',
            '51-200' => '51–200 employees',
            '201-500' => '201–500 employees',
            '500+' => '500+ employees',
        ],
        'positions_count' => [
            '1-5' => '1–5',
            '6-15' => '6–15',
            '16-30' => '16–30',
            '30+' => '30+',
        ],
        'hiring_timeline' => [
            'immediate' => 'Immediately',
            '1-3-months' => '1–3 months',
            '3-6-months' => '3–6 months',
            'flexible' => 'Flexible',
        ],
    ];

    public static function label(string $key): string
    {
        return self::LABELS[$key] ?? str($key)->replace('_', ' ')->title()->toString();
    }

    public static function hasOptions(string $key): bool
    {
        return $key === 'education_level' || array_key_exists($key, self::OPTIONS);
    }

    public static function formatOption(string $key, mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if ($key === 'education_level') {
            return EducationLevel::query()
                ->where('slug', (string) $value)
                ->value('name') ?? (string) $value;
        }

        return self::OPTIONS[$key][(string) $value] ?? (string) $value;
    }

    public static function placeholder(): string
    {
        return '—';
    }

    public static function displayValue(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return (string) $value;
    }

    public static function fileUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public static function fileLabel(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return basename($path);
    }

    public static function fileExtension(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $extension !== '' ? strtoupper($extension) : null;
    }

    /**
     * @return 'image'|'pdf'|null
     */
    public static function filePreviewType(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg', 'png', 'webp', 'gif' => 'image',
            'pdf' => 'pdf',
            default => null,
        };
    }

    public static function fileDownloadUrl(Inquiry $inquiry, string $field): string
    {
        return route('filament.bv-admin.inquiries.documents.download', [
            'inquiry' => $inquiry,
            'field' => $field,
        ]);
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'candidate' => 'Candidate',
            'recruiter' => 'Recruiter',
            default => str($type)->title()->toString(),
        };
    }

    public static function typeColor(string $type): string
    {
        return match ($type) {
            'candidate' => 'info',
            'recruiter' => 'warning',
            default => 'gray',
        };
    }
}
