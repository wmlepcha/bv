<?php

namespace Database\Factories;

use App\Enums\PlacementStatus;
use App\Enums\RegistrationPurpose;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student()->paid(),
            'headline' => fake()->jobTitle(),
            'placement_status' => PlacementStatus::Open,
            'registration_purpose' => RegistrationPurpose::Training,
            'professions' => [],
            'vitae_data' => ['is_newbie' => true],
        ];
    }

    public function enrolled(?Batch $batch = null): static
    {
        return $this->afterCreating(fn (StudentProfile $profile) => BatchEnrollment::factory()->create([
            'student_id' => $profile->user_id,
            'batch_id' => $batch ?? Batch::factory(),
        ]));
    }

    public function forPlacement(): static
    {
        return $this->state(['registration_purpose' => RegistrationPurpose::TrainingAndPlacement]);
    }

    public function hired(): static
    {
        return $this->state(['placement_status' => PlacementStatus::Hired]);
    }

    public function experienced(): static
    {
        return $this->state(['vitae_data' => ['is_newbie' => false]]);
    }

    /**
     * @param  list<string>  $professions
     */
    public function professions(array $professions): static
    {
        return $this->state(['professions' => $professions]);
    }
}
