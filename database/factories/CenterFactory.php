<?php

namespace Database\Factories;

use App\Models\Center;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Center>
 */
class CenterFactory extends Factory
{
    protected $model = Center::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
