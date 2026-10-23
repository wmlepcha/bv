<?php

namespace Database\Factories;

use App\Enums\CourseLevel;
use App\Enums\CoursePace;
use App\Enums\FeedbackSkill;
use App\Models\CourseFeedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseFeedback>
 */
class CourseFeedbackFactory extends Factory
{
    protected $model = CourseFeedback::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => User::factory()->student()->paid(),
            'batch_id' => null,
            'teacher_id' => null,
            'center_id' => null,
            'form_number' => 1,
            'level' => fake()->randomElement(CourseLevel::cases()),
            'overall_satisfaction' => fake()->numberBetween(1, 5),
            'trainer_quality' => fake()->numberBetween(1, 5),
            'explanation_clarity' => fake()->numberBetween(1, 5),
            'class_engagement' => fake()->numberBetween(1, 5),
            'materials_usefulness' => fake()->numberBetween(1, 5),
            'skills_improvement' => fake()->numberBetween(1, 5),
            'confidence' => fake()->numberBetween(1, 5),
            'most_improved_skill' => fake()->randomElement(FeedbackSkill::cases()),
            'needs_improvement_skill' => fake()->randomElement(FeedbackSkill::cases()),
            'pace' => fake()->randomElement(CoursePace::cases()),
            'support_satisfaction' => fake()->numberBetween(1, 5),
            'liked_most' => fake()->sentence(),
            'improvement_suggestion' => fake()->sentence(),
        ];
    }
}
