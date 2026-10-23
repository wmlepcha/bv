<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\AssignmentSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentSection>
 */
class AssignmentSectionFactory extends Factory
{
    protected $model = AssignmentSection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory(),
            'label' => 'A',
            'title' => 'Übung',
            'instructions' => null,
            'sort_order' => 0,
        ];
    }
}
