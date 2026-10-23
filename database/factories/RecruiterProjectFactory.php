<?php

namespace Database\Factories;

use App\Models\RecruiterProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecruiterProject>
 */
class RecruiterProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->recruiter(),
            'name' => fake()->words(3, true),
            'country' => 'Germany',
            'sector' => 'Nursing',
            'timeline' => 'Within 3 months',
        ];
    }
}
