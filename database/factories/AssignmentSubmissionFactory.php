<?php

namespace Database\Factories;

use App\Enums\AssignmentSubmissionStatus;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentSubmission>
 */
class AssignmentSubmissionFactory extends Factory
{
    protected $model = AssignmentSubmission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory()->published(),
            'student_id' => User::factory()->student()->paid(),
            'status' => AssignmentSubmissionStatus::Submitted,
            'submitted_at' => now(),
            'auto_score' => null,
            'final_score' => null,
            'score_overridden' => false,
            'feedback' => null,
            'graded_by' => null,
            'graded_at' => null,
        ];
    }

    public function graded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssignmentSubmissionStatus::Graded,
            'auto_score' => 8,
            'final_score' => 8,
            'score_overridden' => false,
            'feedback' => 'Good work.',
            'graded_by' => User::factory()->trainingManager(),
            'graded_at' => now(),
        ]);
    }
}
