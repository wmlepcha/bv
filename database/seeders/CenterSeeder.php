<?php

namespace Database\Seeders;

use App\Models\Center;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CenterSeeder extends Seeder
{
    public function run(): void
    {
        $centers = [
            ['name' => 'Gangtok', 'sort_order' => 1],
            ['name' => 'Siliguri', 'sort_order' => 2],
            ['name' => 'Manipur', 'sort_order' => 3],
        ];

        foreach ($centers as $center) {
            Center::query()->updateOrCreate(
                ['slug' => Str::slug($center['name'])],
                [
                    'name' => $center['name'],
                    'sort_order' => $center['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
