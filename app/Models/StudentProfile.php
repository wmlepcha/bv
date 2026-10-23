<?php

namespace App\Models;

use App\Enums\CivilStatus;
use App\Enums\Gender;
use App\Enums\PlacementStatus;
use App\Enums\RegistrationPurpose;
use Database\Factories\StudentProfileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $headline
 * @property string|null $introduction
 * @property string|null $mobile_whatsapp
 * @property Carbon|null $date_of_birth
 * @property string|null $nationality
 * @property string|null $current_address
 * @property string|null $passport_photo_path
 * @property string|null $passport_document_path
 * @property list<string>|null $skills
 * @property array<string, mixed>|null $vitae_data
 * @property PlacementStatus $placement_status
 * @property RegistrationPurpose $registration_purpose
 * @property Carbon|null $placement_requested_at
 * @property-read User $user
 */
class StudentProfile extends Model
{
    /** @use HasFactory<StudentProfileFactory> */
    use HasFactory;

    /**
     * Identity documents live on the private disk and are only served through StudentDocumentController.
     */
    public const PRIVATE_DISK = 'local';

    /**
     * @var array<string, string> document kind (URL segment) => profile column
     */
    public const PRIVATE_DOCUMENTS = [
        'passport' => 'passport_document_path',
        'drivers-license' => 'drivers_license_document_path',
    ];

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
        'placement_status',
        'professions',
        'registration_purpose',
        'placement_requested_at',
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
            'placement_status' => PlacementStatus::class,
            'professions' => 'array',
            'registration_purpose' => RegistrationPurpose::class,
            'placement_requested_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<StudentProfile>  $query
     * @param  array{profession?: string|null, experience?: string|null, level?: string|null, status?: string|null, q?: string|null}  $filters
     * @return Builder<StudentProfile>
     */
    public function scopeMatching(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('placement_status', $status))
            ->when($filters['profession'] ?? null, fn (Builder $q, string $profession) => $q->whereJsonContains('professions', $profession))
            ->when($filters['experience'] ?? null, function (Builder $q, string $experience): void {
                if ($experience === 'fresher') {
                    $q->where('vitae_data->is_newbie', true);

                    return;
                }

                $q->where(fn (Builder $inner) => $inner
                    ->where('vitae_data->is_newbie', false)
                    ->orWhereNull('vitae_data->is_newbie')
                    ->orWhereNull('vitae_data'));
            })
            ->when($filters['level'] ?? null, fn (Builder $q, string $level) => $q->whereHas(
                'user.batchEnrollment.batch',
                fn (Builder $b) => $b->where('level', $level),
            ))
            ->when($filters['q'] ?? null, fn (Builder $q, string $term) => $q->whereHas(
                'user',
                fn (Builder $u) => $u->where('name', 'like', '%'.$term.'%'),
            ));
    }

    /**
     * Candidates staff may list: active (paid/activated) registration and enrolled in a batch.
     *
     * @param  Builder<StudentProfile>  $query
     * @return Builder<StudentProfile>
     */
    public function scopeListedForRecruiters(Builder $query): Builder
    {
        return $query->whereHas('user', fn (Builder $u) => $u
            ->where('registration_expires_at', '>', now())
            ->whereHas('batchEnrollment'));
    }

    /**
     * Recruiters and employees only see candidates who registered for Training + Placement.
     *
     * @param  Builder<StudentProfile>  $query
     * @return Builder<StudentProfile>
     */
    public function scopeRegisteredForPlacement(Builder $query): Builder
    {
        return $query->where('registration_purpose', RegistrationPurpose::TrainingAndPlacement);
    }

    /**
     * Whether BV approved this candidate in one of the viewer's own projects.
     */
    public function isApprovedFor(User $viewer): bool
    {
        return RecruiterProjectCandidate::query()
            ->where('student_profile_id', $this->id)
            ->whereNotNull('approved_at')
            ->whereHas('project', fn (Builder $q) => $q->where('user_id', $viewer->id))
            ->exists();
    }

    /**
     * Who may open this student's identity documents: the student, placement staff, their own trainer,
     * and recruiters who can see the full profile.
     */
    public function documentsViewableBy(User $viewer): bool
    {
        return match (true) {
            $viewer->id === $this->user_id, $viewer->isAdmin(), $viewer->isRecruitmentManager() => true,
            $viewer->isTeacher() => BatchEnrollment::query()
                ->where('student_id', $this->user_id)
                ->whereHas('batch', fn (Builder $batch) => $batch->where('teacher_id', $viewer->id))
                ->exists(),
            $viewer->isRecruiter(), $viewer->isEmployee() => $this->wantsPlacement()
                && self::query()->listedForRecruiters()->whereKey($this->id)->exists()
                && $this->isFullyViewableBy($viewer),
            default => false,
        };
    }

    public function wantsPlacement(): bool
    {
        return $this->registration_purpose === RegistrationPurpose::TrainingAndPlacement;
    }

    public function isHired(): bool
    {
        return $this->placement_status === PlacementStatus::Hired;
    }

    /**
     * Recruiters and employees may not open the full profile of a hired candidate.
     */
    public function isFullyViewableBy(User $viewer, bool $honorApproval = true): bool
    {
        if ($viewer->isRecruiter() || $viewer->isEmployee()) {
            return ! $this->isHired() || ($honorApproval && $this->isApprovedFor($viewer));
        }

        return true;
    }

    protected static function booted(): void
    {
        static::saving(function (StudentProfile $profile): void {
            if ($profile->exists && $profile->isDirty('registration_purpose')) {
                $profile->placement_requested_at = null;
            }
        });
    }
}
