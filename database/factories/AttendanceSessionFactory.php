<?php

namespace Database\Factories;

use App\Models\AttendanceSession;
use App\Models\Batch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AttendanceSession>
 */
class AttendanceSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'batch_id' => Batch::factory(),
            'secret' => Str::random(40),
            'ttl_seconds' => 15,
            'started_at' => now(),
            'closed_at' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (): array => ['started_at' => now()->subHours(2), 'closed_at' => now()->subHour()]);
    }
}
