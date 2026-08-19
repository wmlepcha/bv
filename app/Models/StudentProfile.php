<?php

namespace App\Models;

use App\Enums\CivilStatus;
use App\Enums\Gender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'mobile_whatsapp',
        'date_of_birth',
        'place_of_birth',
        'civil_status',
        'gender',
        'secondary_email',
        'secondary_mobile',
        'current_address',
        'permanent_address',
        'nationality',
        'passport_or_id_number',
        'passport_document_path',
        'drivers_license',
        'drivers_license_document_path',
        'passport_photo_path',
        'full_body_photo_path',
        'introduction',
        'hobbies',
        'skills',
        'gap_reasons',
        'current_status',
        'pursuing_details',
        'headline',
        'languages',
        'proficiency_summary',
        'vitae_data',
        'cv_translation_cache',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'civil_status' => CivilStatus::class,
            'gender' => Gender::class,
            'skills' => 'array',
            'languages' => 'array',
            'vitae_data' => 'array',
            'cv_translation_cache' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
