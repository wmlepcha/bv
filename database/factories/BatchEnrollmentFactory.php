<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BatchEnrollment>
 */
class BatchEnrollmentFactory extends Factory
{
    protected $model = BatchEnrollment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'batch_id' => Batch::factory(),
            'student_id' => User::factory()->student()->paid(),
            'transferred_from_batch_id' => null,
            'transferred_by' => null,
            'transferred_at' => null,
        ];
    }
}
