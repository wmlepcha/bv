<?php

namespace Tests\Feature\Assignments;

use App\Enums\AssignmentStatus;
use App\Models\Assignment;
use App\Models\AssignmentFolder;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AssignmentFolderTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_manager_can_create_rename_and_delete_folder(): void
    {
        $manager = User::factory()->trainingManager()->create();

        $this->actingAs($manager)
            ->post(route('training-manager.assignments.folders.store'), [
                'name' => 'Spring worksheets',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $folder = AssignmentFolder::query()->where('name', 'Spring worksheets')->firstOrFail();
        $this->assertSame($manager->id, $folder->created_by);

        $this->actingAs($manager)
            ->put(route('training-manager.assignments.folders.update', $folder), [
                'name' => 'Spring A1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('Spring A1', $folder->fresh()->name);

        $this->actingAs($manager)
            ->delete(route('training-manager.assignments.folders.destroy', $folder))
            ->assertRedirect(route('training-manager.assignments.index', ['status' => 'published']))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('assignment_folders', ['id' => $folder->id]);
    }

    public function test_training_manager_can_move_assignment_into_folder_and_back_to_root(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();
        $assignment = Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'folder_id' => null,
        ]);
        $folder = AssignmentFolder::query()->create([
            'name' => 'Organized',
            'created_by' => $manager->id,
            'sort_order' => 0,
        ]);

        $this->actingAs($manager)
            ->patch(route('training-manager.assignments.folder.move', $assignment), [
                'folder_id' => $folder->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($folder->id, $assignment->fresh()->folder_id);
        $this->assertSame($batch->id, $assignment->fresh()->batch_id);
        $this->assertSame(AssignmentStatus::Published, $assignment->fresh()->status);

        $this->actingAs($manager)
            ->patch(route('training-manager.assignments.folder.move', $assignment), [
                'folder_id' => null,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull($assignment->fresh()->folder_id);
    }

    public function test_deleting_folder_unfiles_assignments_without_deleting_them(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $assignment = Assignment::factory()->published()->create();
        $folder = AssignmentFolder::query()->create([
            'name' => 'Temporary',
            'created_by' => $manager->id,
            'sort_order' => 0,
        ]);
        $assignment->update(['folder_id' => $folder->id]);

        $this->actingAs($manager)
            ->delete(route('training-manager.assignments.folders.destroy', $folder))
            ->assertRedirect();

        $this->assertDatabaseMissing('assignment_folders', ['id' => $folder->id]);
        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'folder_id' => null,
        ]);
    }

    public function test_index_filters_assignments_by_status_and_folder(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();
        $folder = AssignmentFolder::query()->create([
            'name' => 'Published folder',
            'created_by' => $manager->id,
            'sort_order' => 0,
        ]);

        $rootPublished = Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $manager->id,
            'title' => 'Root published',
            'folder_id' => null,
        ]);
        $folderPublished = Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $manager->id,
            'title' => 'Folder published',
            'folder_id' => $folder->id,
        ]);
        Assignment::factory()->create([
            'batch_id' => $batch->id,
            'created_by' => $manager->id,
            'title' => 'Folder draft',
            'status' => AssignmentStatus::Draft,
            'folder_id' => $folder->id,
        ]);

        $this->actingAs($manager)
            ->get(route('training-manager.assignments.index', [
                'creator' => $manager->id,
                'status' => 'published',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/training-manager/assignments/index')
                ->where('status', 'published')
                ->has('assignments', 1)
                ->where('assignments.0.id', $rootPublished->id)
                ->has('folders', 1)
                ->where('folders.0.assignments_count', 1)
                ->where('folders.0.id', $folder->id)
            );

        $this->actingAs($manager)
            ->get(route('training-manager.assignments.index', [
                'creator' => $manager->id,
                'status' => 'published',
                'folder' => $folder->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('currentFolder.id', $folder->id)
                ->has('assignments', 1)
                ->where('assignments.0.id', $folderPublished->id)
            );
    }

    public function test_student_visibility_is_unchanged_after_folder_move(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $student = User::factory()->student()->paid()->create();
        $batch = Batch::factory()->create();
        BatchEnrollment::factory()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);
        $assignment = Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
        ]);
        $folder = AssignmentFolder::query()->create([
            'name' => 'Hidden from TM grid only',
            'created_by' => $manager->id,
            'sort_order' => 0,
        ]);

        $this->actingAs($manager)
            ->patch(route('training-manager.assignments.folder.move', $assignment), [
                'folder_id' => $folder->id,
            ])
            ->assertRedirect();

        $this->actingAs($student)
            ->get(route('student.assignments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('assignments', 1)
                ->where('assignments.0.id', $assignment->id)
            );

        $this->actingAs($student)
            ->get(route('student.assignments.show', $assignment))
            ->assertOk();
    }

    public function test_non_training_manager_cannot_manage_folders(): void
    {
        $teacher = User::factory()->teacher()->create();
        $assignment = Assignment::factory()->published()->create();
        $folder = AssignmentFolder::query()->create([
            'name' => 'TM only',
            'created_by' => User::factory()->trainingManager()->create()->id,
            'sort_order' => 0,
        ]);

        $this->actingAs($teacher)
            ->post(route('training-manager.assignments.folders.store'), ['name' => 'Nope'])
            ->assertForbidden();

        $this->actingAs($teacher)
            ->put(route('training-manager.assignments.folders.update', $folder), ['name' => 'Nope'])
            ->assertForbidden();

        $this->actingAs($teacher)
            ->delete(route('training-manager.assignments.folders.destroy', $folder))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->patch(route('training-manager.assignments.folder.move', $assignment), [
                'folder_id' => $folder->id,
            ])
            ->assertForbidden();
    }
}
