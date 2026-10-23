<?php

namespace App\Models;

use App\Enums\AvatarAnimal;
use App\Enums\RegistrationFeePaidVia;
use App\Enums\SpecialId;
use App\Enums\UserRole;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property UserRole $role
 * @property string|null $state_code
 * @property SpecialId|null $special_id
 * @property int|null $registration_year
 * @property string|null $student_number
 * @property Carbon|null $registration_paid_at
 * @property Carbon|null $dashboard_activated_at
 * @property RegistrationFeePaidVia|null $registration_fee_paid_via
 * @property Carbon|null $registration_expires_at
 * @property int|null $registration_plan_id
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property string|null $avatar_path
 * @property string|null $avatar_animal
 * @property string|null $whatsapp_number
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'first_name', 'last_name', 'email', 'password', 'whatsapp_number'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Number of course feedback forms each student fills in.
     */
    public const COURSE_FEEDBACK_FORM_COUNT = 2;

    /**
     * Weeks between dashboard activation and the first feedback form unlocking.
     */
    public const COURSE_FEEDBACK_FIRST_FORM_DELAY_WEEKS = 4;

    /**
     * Weeks between submitting the first feedback form and the second one unlocking.
     */
    public const COURSE_FEEDBACK_SECOND_FORM_DELAY_WEEKS = 5;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'special_id' => SpecialId::class,
            'registration_year' => 'integer',
            'registration_paid_at' => 'datetime',
            'dashboard_activated_at' => 'datetime',
            'registration_expires_at' => 'datetime',
            'registration_fee_paid_via' => RegistrationFeePaidVia::class,
            'registration_plan_id' => 'integer',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    public function isRecruiter(): bool
    {
        return $this->role === UserRole::Recruiter;
    }

    public function isSalesTeam(): bool
    {
        return $this->role === UserRole::SalesTeam;
    }

    public function isRecruitmentManager(): bool
    {
        return $this->role === UserRole::RecruitmentManager;
    }

    public function isEmployee(): bool
    {
        return $this->role === UserRole::Employee;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isTeacher(): bool
    {
        return $this->role === UserRole::Teacher;
    }

    public function isTrainingManager(): bool
    {
        return $this->role === UserRole::TrainingManager;
    }

    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    public function hasActiveRegistration(): bool
    {
        if (! $this->isStudent()) {
            return true;
        }

        return $this->registration_expires_at?->isFuture() ?? false;
    }

    public function hasPaidRegistration(): bool
    {
        return $this->hasActiveRegistration();
    }

    /**
     * Form 1 unlocks a fixed number of weeks after dashboard activation.
     * Form 2 unlocks a fixed number of weeks after form 1 was submitted.
     */
    public function courseFeedbackFormAvailableAt(int $formNumber): ?CarbonInterface
    {
        if ($formNumber === 1) {
            return $this->dashboard_activated_at?->addWeeks(self::COURSE_FEEDBACK_FIRST_FORM_DELAY_WEEKS);
        }

        $firstForm = $this->courseFeedback()->where('form_number', 1)->first();

        return $firstForm?->created_at?->addWeeks(self::COURSE_FEEDBACK_SECOND_FORM_DELAY_WEEKS);
    }

    /**
     * The earliest unlocked feedback form the student has not submitted yet.
     */
    public function openCourseFeedbackFormNumber(): ?int
    {
        if (! $this->isStudent() || ! $this->hasActiveRegistration()) {
            return null;
        }

        $submitted = $this->courseFeedback()->pluck('form_number')->all();

        foreach (range(1, self::COURSE_FEEDBACK_FORM_COUNT) as $formNumber) {
            if (in_array($formNumber, $submitted, true)) {
                continue;
            }

            return $this->courseFeedbackFormAvailableAt($formNumber)?->isPast() ? $formNumber : null;
        }

        return null;
    }

    public function canSubmitCourseFeedback(): bool
    {
        return $this->openCourseFeedbackFormNumber() !== null;
    }

    public function canAccessDashboard(): bool
    {
        if ($this->isStudent()) {
            return $this->hasActiveRegistration();
        }

        return true;
    }

    public function avatarUrl(): ?string
    {
        if (blank($this->avatar_path)) {
            return null;
        }

        return '/storage/'.ltrim(str_replace('\\', '/', $this->avatar_path), '/');
    }

    /**
     * The chosen cartoon avatar, or a stable default derived from the user id.
     */
    public function avatarAnimal(): AvatarAnimal
    {
        return AvatarAnimal::tryFrom((string) $this->avatar_animal) ?? AvatarAnimal::defaultFor($this->id);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAuthArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'role' => $this->role->value,
            'student_number' => $this->student_number,
            'registration_paid_at' => $this->registration_paid_at,
            'registration_expires_at' => $this->registration_expires_at,
            'registration_plan_id' => $this->registration_plan_id,
            'can_access_dashboard' => $this->canAccessDashboard(),
            'avatar_url' => $this->avatarUrl(),
            'avatar_animal' => $this->avatarAnimal()->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function registrationPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'registration_plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * The number WhatsApp updates go to: the student's own, or the one set on a staff account.
     */
    public function whatsAppNumber(): ?string
    {
        return $this->studentProfile?->mobile_whatsapp ?: $this->whatsapp_number;
    }

    /**
     * @return HasMany<RecruiterProject, $this>
     */
    public function recruiterProjects(): HasMany
    {
        return $this->hasMany(RecruiterProject::class);
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function teachingBatches(): HasMany
    {
        return $this->hasMany(Batch::class, 'teacher_id');
    }

    public function createdAssignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'created_by');
    }

    public function materialAssignments(): HasMany
    {
        return $this->hasMany(MaterialAssignment::class, 'trainer_id');
    }

    public function learningItems(): HasMany
    {
        return $this->hasMany(LearningItem::class);
    }

    /**
     * @return HasOne<BatchEnrollment, $this>
     */
    public function batchEnrollment(): HasOne
    {
        return $this->hasOne(BatchEnrollment::class, 'student_id');
    }

    /**
     * @return HasMany<CourseFeedback, $this>
     */
    public function courseFeedback(): HasMany
    {
        return $this->hasMany(CourseFeedback::class, 'student_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() || $this->isSalesTeam();
    }
}
