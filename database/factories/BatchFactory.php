<?php

namespace Database\Factories;

use App\Enums\CourseLanguage;
use App\Enums\CourseLevel;
use App\Models\Batch;
use App\Models\Center;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Batch>
 */
class BatchFactory extends Factory
{
    protected $model = Batch::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'center_id' => Center::query()->inRandomOrder()->value('id')
                ?? Center::factory(),
            'language' => CourseLanguage::German,
            'level' => CourseLevel::B1,
            'teacher_id' => User::factory()->teacher(),
            'is_active' => true,
        ];
    }
}
