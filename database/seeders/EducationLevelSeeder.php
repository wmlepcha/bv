<?php

namespace Database\Seeders;

use App\Models\EducationLevel;
use Illuminate\Database\Seeder;

class EducationLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['slug' => 'high-school', 'name' => 'High school', 'sort_order' => 1],
            ['slug' => 'diploma', 'name' => 'Diploma', 'sort_order' => 2],
            ['slug' => 'bachelors', 'name' => "Bachelor's degree", 'sort_order' => 3],
            ['slug' => 'masters', 'name' => "Master's degree", 'sort_order' => 4],
            ['slug' => 'doctorate', 'name' => 'Doctorate', 'sort_order' => 5],
            ['slug' => 'other', 'name' => 'Other', 'sort_order' => 6],
        ];

        foreach ($levels as $level) {
            EducationLevel::query()->updateOrCreate(
                ['slug' => $level['slug']],
                [
                    'name' => $level['name'],
                    'sort_order' => $level['sort_order'],
                    'is_visible' => true,
                ],
            );
        }
    }
}
