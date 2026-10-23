<?php

namespace Tests\Feature\Student;

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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LearningTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_add_accessible_file_to_my_learning(): void
    {
        [$student, , , , $file] = $this->sharedFileForStudent();

        $this->actingAs($student)
            ->post(route('student.my-learning.store'), ['material_file_id' => $file->id])
            ->assertRedirect();

        $this->assertDatabaseHas('learning_items', [
            'user_id' => $student->id,
            'material_file_id' => $file->id,
        ]);
    }

    public function test_adding_the_same_file_twice_does_not_duplicate(): void
    {
        [$student, , , , $file] = $this->sharedFileForStudent();

        $this->actingAs($student)->post(route('student.my-learning.store'), ['material_file_id' => $file->id]);
        $this->actingAs($student)->post(route('student.my-learning.store'), ['material_file_id' => $file->id]);

        $this->assertDatabaseCount('learning_items', 1);
    }

    public function test_student_cannot_save_a_file_they_cannot_access(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $student = User::factory()->student()->paid()->create();

        $library = app(MaterialLibraryService::class);
        $folder = $library->createFolder($manager, 'Private');
        $file = $library->uploadFile(
            $manager,
            $folder,
            UploadedFile::fake()->create('secret.pdf', 100, 'application/pdf'),
        );

        $this->actingAs($student)
            ->post(route('student.my-learning.store'), ['material_file_id' => $file->id])
            ->assertForbidden();

        $this->assertDatabaseCount('learning_items', 0);
    }

    public function test_my_learning_index_lists_saved_items_with_notes(): void
    {
        [$student, , , , $file] = $this->sharedFileForStudent();

        $item = LearningItem::query()->create([
            'user_id' => $student->id,
            'material_file_id' => $file->id,
            'note' => 'Review this before the test.',
        ]);

        $this->actingAs($student)
            ->get(route('student.my-learning.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/student/my-learning/index')
                ->has('items', 1)
                ->where('items.0.id', $item->id)
                ->where('items.0.note', 'Review this before the test.')
                ->where('items.0.material.id', $file->id)
            );
    }

    public function test_student_can_update_note_on_their_own_learning_item(): void
    {
        [$student, , , , $file] = $this->sharedFileForStudent();

        $item = LearningItem::query()->create([
            'user_id' => $student->id,
            'material_file_id' => $file->id,
        ]);

        $this->actingAs($student)
            ->patch(route('student.my-learning.note.update', $item), ['note' => 'Updated note'])
            ->assertRedirect();

        $this->assertDatabaseHas('learning_items', [
            'id' => $item->id,
            'note' => 'Updated note',
        ]);
    }

    public function test_student_cannot_update_note_on_another_students_learning_item(): void
    {
        [$student, , , , $file] = $this->sharedFileForStudent();
        $other = User::factory()->student()->paid()->create();

        $item = LearningItem::query()->create([
            'user_id' => $student->id,
            'material_file_id' => $file->id,
        ]);

        $this->actingAs($other)
            ->patch(route('student.my-learning.note.update', $item), ['note' => 'Hijacked'])
            ->assertNotFound();

        $this->assertDatabaseHas('learning_items', [
            'id' => $item->id,
            'note' => null,
        ]);
    }

    public function test_student_can_remove_their_learning_item(): void
    {
        [$student, , , , $file] = $this->sharedFileForStudent();

        $item = LearningItem::query()->create([
            'user_id' => $student->id,
            'material_file_id' => $file->id,
        ]);

        $this->actingAs($student)
            ->delete(route('student.my-learning.destroy', $item))
            ->assertRedirect();

        $this->assertDatabaseMissing('learning_items', ['id' => $item->id]);
    }

    public function test_study_bank_marks_saved_files_with_learning_item_id(): void
    {
        [$student, , $folder, , $file] = $this->sharedFileForStudent();

        $item = LearningItem::query()->create([
            'user_id' => $student->id,
            'material_file_id' => $file->id,
        ]);

        $this->actingAs($student)
            ->get(route('student.study-bank.folders.show', $folder))
            ->assertInertia(fn (Assert $page) => $page
                ->where('files.0.learning_item_id', $item->id)
            );
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
