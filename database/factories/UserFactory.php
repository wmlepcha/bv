<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'role' => UserRole::Student,
            'student_number' => null,
            'first_name' => null,
            'last_name' => null,
            'state_code' => null,
            'special_id' => null,
            'registration_year' => null,
            'registration_paid_at' => null,
            'dashboard_activated_at' => null,
            'registration_expires_at' => null,
            'registration_plan_id' => null,
            'registration_fee_paid_via' => null,
        ];
    }

    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Student,
            'student_number' => $attributes['student_number'] ?? 'SK-COI-2026-'.fake()->unique()->numerify('##'),
            'state_code' => $attributes['state_code'] ?? 'SK',
            'special_id' => $attributes['special_id'] ?? 'COI',
            'registration_year' => $attributes['registration_year'] ?? 2026,
        ]);
    }

    public function recruiter(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Recruiter,
            'registration_paid_at' => now(),
        ]);
    }

    public function employee(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Employee,
            'registration_paid_at' => now(),
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
            'registration_paid_at' => now(),
        ]);
    }

    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Teacher,
            'registration_paid_at' => now(),
        ]);
    }

    public function trainingManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::TrainingManager,
            'registration_paid_at' => now(),
        ]);
    }

    public function salesTeam(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::SalesTeam,
            'registration_paid_at' => now(),
        ]);
    }

    public function recruitmentManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::RecruitmentManager,
            'registration_paid_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'registration_paid_at' => now(),
            'dashboard_activated_at' => now(),
            'registration_expires_at' => now()->addYears(2),
            'student_number' => $attributes['student_number'] ?? 'SK-COI-2026-'.fake()->unique()->numerify('##'),
            'state_code' => $attributes['state_code'] ?? 'SK',
            'special_id' => $attributes['special_id'] ?? 'COI',
            'registration_year' => $attributes['registration_year'] ?? 2026,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'registration_paid_at' => now()->subYears(2),
            'dashboard_activated_at' => now()->subYears(2),
            'registration_expires_at' => now()->subDay(),
            'student_number' => $attributes['student_number'] ?? 'SK-COI-2026-'.fake()->unique()->numerify('##'),
            'state_code' => $attributes['state_code'] ?? 'SK',
            'special_id' => $attributes['special_id'] ?? 'COI',
            'registration_year' => $attributes['registration_year'] ?? 2026,
        ]);
    }

    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'registration_paid_at' => null,
            'dashboard_activated_at' => null,
            'registration_expires_at' => null,
            'registration_plan_id' => null,
            'student_number' => $attributes['student_number'] ?? 'SK-COI-2026-'.fake()->unique()->numerify('##'),
            'state_code' => $attributes['state_code'] ?? 'SK',
            'special_id' => $attributes['special_id'] ?? 'COI',
            'registration_year' => $attributes['registration_year'] ?? 2026,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
