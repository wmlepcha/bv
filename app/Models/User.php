<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RegistrationFeePaidVia;
use App\Enums\SpecialId;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
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
 * @property RegistrationFeePaidVia|null $registration_fee_paid_via
 * @property Carbon|null $registration_expires_at
 * @property int|null $registration_plan_id
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property string|null $avatar_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'first_name', 'last_name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

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

    public function canAccessDashboard(): bool
    {
        if ($this->isStudent()) {
            return $this->hasActiveRegistration();
        }

        return true;
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

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function teachingBatches(): HasMany
    {
        return $this->hasMany(Batch::class, 'teacher_id');
    }

    public function batchEnrollment(): HasOne
    {
        return $this->hasOne(BatchEnrollment::class, 'student_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }
}
