<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\LearningItem;
use App\Models\MaterialFile;
use App\Models\MaterialFolder;
use App\Models\User;
use App\Services\MaterialAssignmentService;
use App\Services\MaterialLibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SidebarSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_short_query_returns_no_results(): void
    {
        $student = User::factory()->student()->paid()->create();

        $this->actingAs($student)
            ->getJson(route('search', ['q' => 'a']))
            ->assertOk()
            ->assertJson(['results' => []]);
    }

    public function test_student_can_find_accessible_folder(): void
    {
        [$student, , $folder] = $this->sharedFileForStudent();

        $this->actingAs($student)
            ->getJson(route('search', ['q' => 'German']))
            ->assertOk()
            ->assertJsonFragment(['label' => $folder->name, 'group' => 'Study Bank']);
    }

    public function test_student_cannot_find_inaccessible_folder(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $student = User::factory()->student()->paid()->create();

        app(MaterialLibraryService::class)->createFolder($manager, 'Secret Vault');

        $this->actingAs($student)
            ->getJson(route('search', ['q' => 'Secret']))
            ->assertOk()
            ->assertJson(['results' => []]);
    }

    public function test_student_can_find_saved_learning_item(): void
    {
        [$student, , , , $file] = $this->sharedFileForStudent();

        $item = LearningItem::query()->create([
            'user_id' => $student->id,
            'material_file_id' => $file->id,
        ]);

        $response = $this->actingAs($student)
            ->getJson(route('search', ['q' => 'lesson']))
            ->assertOk();

        $response->assertJsonFragment(['group' => 'My Learning']);
        $this->assertStringContainsString(
            (string) $item->id,
            collect($response->json('results'))->firstWhere('group', 'My Learning')['href'],
        );
    }

    public function test_non_student_role_receives_empty_results(): void
    {
        [, , $folder] = $this->sharedFileForStudent();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->getJson(route('search', ['q' => $folder->name]))
            ->assertOk()
            ->assertJson(['results' => []]);
    }

    /**
     * @return array{0: User, 1: Batch, 2: MaterialFolder, 3: User, 4: MaterialFile}
     */
    private function sharedFileForStudent(): array
    {
        Notification::fake();
        Storage::fake('local');

        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create(['is_active' => true]);
        $student = User::factory()->student()->paid()->create();
        BatchEnrollment::factory()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        $folder = app(MaterialLibraryService::class)->createFolder($manager, 'A1 - German');
        $file = app(MaterialLibraryService::class)->uploadFile(
            $manager,
            $folder,
            UploadedFile::fake()->create('lesson.pdf', 100, 'application/pdf'),
        );
        app(MaterialAssignmentService::class)->shareFolderWithBatches($manager, $folder, [$batch->id]);

        return [$student, $batch, $folder, $manager, $file];
    }
}
