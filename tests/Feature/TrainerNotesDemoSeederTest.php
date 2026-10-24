<?php

namespace Tests\Feature;

use App\Enums\CourseLanguage;
use App\Enums\CourseLevel;
use App\Enums\SubmissionAnnotationType;
use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Center;
use App\Models\SubmissionAnnotation;
use App\Models\User;
use Database\Seeders\TrainerNotesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerNotesDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_every_note_type_once(): void
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

        $this->seed(TrainerNotesDemoSeeder::class);
        $this->seed(TrainerNotesDemoSeeder::class);

        foreach (SubmissionAnnotationType::cases() as $type) {
            $this->assertTrue(SubmissionAnnotation::query()->where('type', $type)->exists(), "Missing {$type->value}");
        }

        $this->assertSame(19, SubmissionAnnotation::query()->count());
        $this->assertTrue(SubmissionAnnotation::query()->whereNull('question_id')->exists());
    }
}
