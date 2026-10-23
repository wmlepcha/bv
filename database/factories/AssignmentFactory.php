<?php

namespace Database\Factories;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentTargetScope;
use App\Models\Assignment;
use App\Models\AssignmentTarget;
use App\Models\Batch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'batch_id' => Batch::factory(),
            'created_by' => User::factory()->teacher(),
            'title' => fake()->sentence(4),
            'instructions' => fake()->optional()->paragraph(),
            'due_at' => now()->addWeek(),
            'max_score' => 10,
            'status' => AssignmentStatus::Draft,
            'audio_disk' => null,
            'audio_path' => null,
            'audio_original_name' => null,
            'published_at' => null,
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Assignment $assignment): void {
            if ($assignment->targets()->count() === 0) {
                AssignmentTarget::query()->create([
                    'assignment_id' => $assignment->id,
                    'scope' => AssignmentTargetScope::Batch,
                    'student_id' => null,
                ]);
            }
        });
    }

    public function pendingApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssignmentStatus::PendingApproval,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssignmentStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssignmentStatus::Rejected,
            'rejection_reason' => 'Please revise the questions.',
        ]);
    }

    public function createdByTrainingManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => User::factory()->trainingManager(),
            'status' => AssignmentStatus::Published,
            'published_at' => now(),
        ]);
    }
}
