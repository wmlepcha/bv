<?php

namespace Tests\Feature;

use App\Enums\AssignmentQuestionType;
use App\Enums\CourseLanguage;
use App\Enums\CourseLevel;
use App\Enums\UserRole;
use App\Models\Assignment;
use App\Models\AssignmentQuestion;
use App\Models\Batch;
use App\Models\Center;
use App\Models\User;
use Database\Seeders\StudentExperienceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentExperienceDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_every_question_type_and_is_idempotent(): void
    {
        $teacher = User::factory()->create(['email' => 'teacher@demo.bilingualvitae.com', 'role' => UserRole::Teacher]);
        User::factory()->create(['email' => 'wilburlepcha@outlook.com', 'role' => UserRole::Student]);
        Batch::factory()->create([
            'name' => 'German B1 · Morning · Demo',
            'center_id' => Center::factory()->create()->id,
            'teacher_id' => $teacher->id,
            'language' => CourseLanguage::German,
            'level' => CourseLevel::B1,
        ]);

        $this->seed(StudentExperienceDemoSeeder::class);
        $this->seed(StudentExperienceDemoSeeder::class);

        $this->assertSame(4, Assignment::query()->count());

        foreach (AssignmentQuestionType::cases() as $type) {
            $this->assertTrue(AssignmentQuestion::query()->where('type', $type)->exists(), "Missing {$type->value}");
        }
    }
}
