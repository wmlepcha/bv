<?php

namespace Tests\Feature\TrainingManager;

use App\Enums\CourseLanguage;
use App\Enums\CourseLevel;
use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Center;
use App\Models\MaterialFile;
use App\Models\MaterialFolder;
use App\Models\User;
use App\Services\MaterialAssignmentService;
use App\Services\MaterialLibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrainingManagerPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_manager_can_create_trainer(): void
    {
        $manager = User::factory()->trainingManager()->create();

        $this->actingAs($manager)
            ->post(route('training-manager.trainers.store'), [
                'name' => 'New Trainer',
                'email' => 'trainer@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('training-manager.trainers.index'));

        $trainer = User::query()->where('email', 'trainer@example.com')->first();

        $this->assertNotNull($trainer);
        $this->assertSame(UserRole::Teacher, $trainer->role);
        $this->assertNotNull($trainer->registration_paid_at);
    }

    public function test_training_manager_can_create_batch_and_assign_trainer(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $trainer = User::factory()->teacher()->create();
        $center = Center::factory()->create();

        $response = $this->actingAs($manager)
            ->post(route('training-manager.batches.store'), [
                'name' => 'German B1 Morning',
                'center_id' => $center->id,
                'language' => CourseLanguage::German->value,
                'level' => CourseLevel::B1->value,
                'teacher_id' => $trainer->id,
                'is_active' => '1',
            ]);

        $batch = Batch::query()->where('name', 'German B1 Morning')->first();

        $this->assertNotNull($batch);
        $this->assertSame($trainer->id, $batch->teacher_id);
        $response->assertRedirect(route('training-manager.batches.students', $batch));
    }

    public function test_training_manager_can_toggle_batch_active_from_index(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create(['is_active' => true]);

        $this->actingAs($manager)
            ->patch(route('training-manager.batches.active', $batch), [
                'is_active' => false,
            ])
            ->assertRedirect(route('training-manager.batches.index'));

        $this->assertFalse($batch->fresh()->is_active);

        $this->actingAs($manager)
            ->patch(route('training-manager.batches.active', $batch), [
                'is_active' => true,
            ])
            ->assertRedirect(route('training-manager.batches.index'));

        $this->assertTrue($batch->fresh()->is_active);
    }

    public function test_training_manager_can_manage_library_and_assign_to_trainer(): void
    {
        Storage::fake('local');

        $manager = User::factory()->trainingManager()->create();
        $trainer = User::factory()->teacher()->create();
        $otherTrainer = User::factory()->teacher()->create();

        $library = app(MaterialLibraryService::class);
        $assignments = app(MaterialAssignmentService::class);

        $folder = $library->createFolder($manager, 'A1 Materials');
        $subfolder = $library->createFolder($manager, 'Grammar', $folder->id);

        $file = $library->uploadFile(
            $manager,
            $subfolder,
            UploadedFile::fake()->create('lesson.pdf', 100, 'application/pdf'),
        );

        $library->renameFile($file, 'Lesson One');

        $this->assertSame('Lesson One', $file->fresh()->display_name);

        $assignments->assignFolder($manager, $folder, [$trainer->id]);

        $this->assertTrue($assignments->trainerCanAccessFile($trainer, $file->fresh()));
        $this->assertFalse($assignments->trainerCanAccessFile($otherTrainer, $file->fresh()));

        $this->actingAs($trainer)
            ->get(route('teacher.materials.files.download', $file))
            ->assertOk();

        $this->actingAs($otherTrainer)
            ->get(route('teacher.materials.files.download', $file))
            ->assertForbidden();
    }

    public function test_non_training_manager_cannot_access_portal_routes(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->get(route('training-manager.dashboard'))
            ->assertForbidden();
    }

    public function test_folder_assignment_lists_on_teacher_materials_page(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $trainer = User::factory()->teacher()->create();
        $library = app(MaterialLibraryService::class);
        $assignments = app(MaterialAssignmentService::class);

        $folder = $library->createFolder($manager, 'Assigned Pack');
        $assignments->assignFolder($manager, $folder, [$trainer->id]);

        $this->actingAs($trainer)
            ->get(route('teacher.materials.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('app/teacher/materials/index')
                ->has('materials.folders', 1)
                ->has('batches')
            );
    }

    public function test_teacher_folder_view_includes_breadcrumb_trail(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $trainer = User::factory()->teacher()->create();
        $library = app(MaterialLibraryService::class);
        $assignments = app(MaterialAssignmentService::class);

        $root = $library->createFolder($manager, 'A1 - German');
        $child = $library->createFolder($manager, 'New Students', $root->id);
        $assignments->assignFolder($manager, $root, [$trainer->id]);

        $this->actingAs($trainer)
            ->get(route('teacher.materials.folders.show', $child))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('app/teacher/materials/folder')
                ->has('breadcrumbs', 2)
                ->where('breadcrumbs.0.name', 'A1 - German')
                ->where('breadcrumbs.1.name', 'New Students')
            );
    }

    public function test_teacher_can_share_material_with_their_batches_only(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $trainer = User::factory()->teacher()->create();
        $otherTrainer = User::factory()->teacher()->create();
        $center = Center::factory()->create();

        $ownBatch = Batch::factory()->create([
            'center_id' => $center->id,
            'teacher_id' => $trainer->id,
            'name' => 'My Morning Batch',
        ]);
        $otherBatch = Batch::factory()->create([
            'center_id' => $center->id,
            'teacher_id' => $otherTrainer->id,
            'name' => 'Other Batch',
        ]);

        $library = app(MaterialLibraryService::class);
        $assignments = app(MaterialAssignmentService::class);
        $folder = $library->createFolder($manager, 'Share Pack');
        $assignments->assignFolder($manager, $folder, [$trainer->id]);

        $this->actingAs($trainer)
            ->post(route('teacher.materials.folders.share', $folder), [
                'batch_ids' => [$ownBatch->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('material_batch_assignments', [
            'assignable_type' => MaterialFolder::class,
            'assignable_id' => $folder->id,
            'batch_id' => $ownBatch->id,
            'assigned_by' => $trainer->id,
        ]);

        $this->actingAs($trainer)
            ->post(route('teacher.materials.folders.share', $folder), [
                'batch_ids' => [$otherBatch->id],
            ])
            ->assertSessionHasErrors('batch_ids');
    }

    public function test_teacher_share_modal_only_lists_batches_assigned_to_them(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $trainer = User::factory()->teacher()->create();
        $otherTrainer = User::factory()->teacher()->create();
        $center = Center::factory()->create();

        Batch::factory()->create([
            'center_id' => $center->id,
            'teacher_id' => $otherTrainer->id,
            'name' => 'Not Mine',
            'is_active' => true,
        ]);

        $ownBatch = Batch::factory()->create([
            'center_id' => $center->id,
            'teacher_id' => $trainer->id,
            'name' => 'Mine',
            'is_active' => true,
        ]);

        Batch::factory()->create([
            'center_id' => $center->id,
            'teacher_id' => $trainer->id,
            'name' => 'Inactive Mine',
            'is_active' => false,
        ]);

        $library = app(MaterialLibraryService::class);
        $assignments = app(MaterialAssignmentService::class);
        $folder = $library->createFolder($manager, 'Share Pack');
        $assignments->assignFolder($manager, $folder, [$trainer->id]);

        $this->actingAs($trainer)
            ->get(route('teacher.materials.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('app/teacher/materials/index')
                ->has('batches', 1)
                ->where('batches.0.id', $ownBatch->id)
                ->where('batches.0.name', 'Mine')
            );

        $unassignedTrainer = User::factory()->teacher()->create();
        $assignments->assignFolder($manager, $folder, [$trainer->id, $unassignedTrainer->id]);

        $this->actingAs($unassignedTrainer)
            ->get(route('teacher.materials.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('app/teacher/materials/index')
                ->has('batches', 0)
            );
    }

    public function test_training_manager_can_add_youtube_and_website_links(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $library = app(MaterialLibraryService::class);
        $folder = $library->createFolder($manager, 'Media Pack');

        $this->actingAs($manager)
            ->post(route('training-manager.library.youtube.store', $folder), [
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'title' => 'Grammar intro',
            ])
            ->assertRedirect(route('training-manager.library.index', ['folder' => $folder->id]));

        $youtube = MaterialFile::query()
            ->where('folder_id', $folder->id)
            ->where('kind', MaterialFile::KIND_YOUTUBE)
            ->first();

        $this->assertNotNull($youtube);
        $this->assertSame('Grammar intro', $youtube->display_name);
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $youtube->url);
        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            $youtube->embedUrl(),
        );

        $this->actingAs($manager)
            ->post(route('training-manager.library.links.store', $folder), [
                'url' => 'https://example.com/lesson',
                'title' => 'Lesson site',
            ])
            ->assertRedirect(route('training-manager.library.index', ['folder' => $folder->id]));

        $this->assertDatabaseHas('material_files', [
            'folder_id' => $folder->id,
            'kind' => MaterialFile::KIND_LINK,
            'display_name' => 'Lesson site',
            'url' => 'https://example.com/lesson',
            'extension' => 'link',
        ]);
    }

    public function test_youtube_endpoint_rejects_invalid_urls(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $folder = app(MaterialLibraryService::class)->createFolder($manager, 'Media Pack');

        $this->actingAs($manager)
            ->from(route('training-manager.library.index', ['folder' => $folder->id]))
            ->post(route('training-manager.library.youtube.store', $folder), [
                'url' => 'https://example.com/not-youtube',
            ])
            ->assertSessionHasErrors('url');

        $this->assertDatabaseMissing('material_files', [
            'folder_id' => $folder->id,
            'kind' => MaterialFile::KIND_YOUTUBE,
        ]);
    }

    public function test_link_endpoint_rejects_youtube_urls(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $folder = app(MaterialLibraryService::class)->createFolder($manager, 'Media Pack');

        $this->actingAs($manager)
            ->from(route('training-manager.library.index', ['folder' => $folder->id]))
            ->post(route('training-manager.library.links.store', $folder), [
                'url' => 'https://youtu.be/dQw4w9WgXcQ',
            ])
            ->assertSessionHasErrors('url');
    }

    public function test_download_returns_not_found_for_youtube_and_link_materials(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $library = app(MaterialLibraryService::class);
        $folder = $library->createFolder($manager, 'Media Pack');

        $youtube = $library->addYoutube(
            $manager,
            $folder,
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'Video',
        );
        $link = $library->addLink(
            $manager,
            $folder,
            'https://example.com',
            'Site',
        );

        $this->actingAs($manager)
            ->get(route('training-manager.library.files.download', $youtube))
            ->assertNotFound();

        $this->actingAs($manager)
            ->get(route('training-manager.library.files.download', $link))
            ->assertNotFound();
    }

    public function test_file_upload_still_works_alongside_link_kinds(): void
    {
        Storage::fake('local');

        $manager = User::factory()->trainingManager()->create();
        $folder = app(MaterialLibraryService::class)->createFolder($manager, 'Docs');

        $this->actingAs($manager)
            ->post(route('training-manager.library.files.store', $folder), [
                'file' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('training-manager.library.index', ['folder' => $folder->id]));

        $this->assertDatabaseHas('material_files', [
            'folder_id' => $folder->id,
            'kind' => MaterialFile::KIND_FILE,
            'extension' => 'pdf',
        ]);
    }

    public function test_training_manager_can_move_youtube_between_folders(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $library = app(MaterialLibraryService::class);
        $source = $library->createFolder($manager, 'Source');
        $destination = $library->createFolder($manager, 'Destination');

        $youtube = $library->addYoutube(
            $manager,
            $source,
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'Intro',
        );

        $this->actingAs($manager)
            ->post(route('training-manager.library.files.move', $youtube), [
                'folder_id' => $destination->id,
            ])
            ->assertRedirect(route('training-manager.library.index', ['folder' => $source->id]));

        $this->assertSame($destination->id, $youtube->fresh()->folder_id);
    }

    public function test_training_manager_can_move_and_copy_files_with_storage(): void
    {
        Storage::fake('local');

        $manager = User::factory()->trainingManager()->create();
        $library = app(MaterialLibraryService::class);
        $assignments = app(MaterialAssignmentService::class);
        $trainer = User::factory()->teacher()->create();

        $source = $library->createFolder($manager, 'Source');
        $destination = $library->createFolder($manager, 'Destination');

        $file = $library->uploadFile(
            $manager,
            $source,
            UploadedFile::fake()->create('lesson.pdf', 100, 'application/pdf'),
        );

        $assignments->assignFile($manager, $file, [$trainer->id]);
        $originalPath = $file->path;

        Storage::disk('local')->assertExists($originalPath);

        $this->actingAs($manager)
            ->post(route('training-manager.library.files.move', $file), [
                'folder_id' => $destination->id,
            ])
            ->assertRedirect(route('training-manager.library.index', ['folder' => $source->id]));

        $moved = $file->fresh();
        $this->assertSame($destination->id, $moved->folder_id);
        $this->assertNotSame($originalPath, $moved->path);
        Storage::disk('local')->assertMissing($originalPath);
        Storage::disk('local')->assertExists($moved->path);
        $this->assertTrue($assignments->trainerCanAccessFile($trainer, $moved));

        $otherDestination = $library->createFolder($manager, 'Other');

        $this->actingAs($manager)
            ->post(route('training-manager.library.files.copy', $moved), [
                'folder_id' => $otherDestination->id,
            ])
            ->assertRedirect(route('training-manager.library.index', ['folder' => $destination->id]));

        $copy = MaterialFile::query()
            ->where('folder_id', $otherDestination->id)
            ->where('display_name', $moved->display_name)
            ->whereKeyNot($moved->id)
            ->first();

        $this->assertNotNull($copy);
        $this->assertNotSame($moved->path, $copy->path);
        Storage::disk('local')->assertExists($moved->path);
        Storage::disk('local')->assertExists($copy->path);
        $this->assertFalse($assignments->trainerCanAccessFile($trainer, $copy));
        $this->assertDatabaseMissing('material_assignments', [
            'assignable_type' => MaterialFile::class,
            'assignable_id' => $copy->id,
        ]);
    }

    public function test_move_and_copy_reject_same_folder(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $library = app(MaterialLibraryService::class);
        $folder = $library->createFolder($manager, 'Only');

        $link = $library->addLink($manager, $folder, 'https://example.com', 'Site');

        $this->actingAs($manager)
            ->from(route('training-manager.library.index', ['folder' => $folder->id]))
            ->post(route('training-manager.library.files.move', $link), [
                'folder_id' => $folder->id,
            ])
            ->assertSessionHasErrors('folder_id');

        $this->actingAs($manager)
            ->from(route('training-manager.library.index', ['folder' => $folder->id]))
            ->post(route('training-manager.library.files.copy', $link), [
                'folder_id' => $folder->id,
            ])
            ->assertSessionHasErrors('folder_id');
    }

    public function test_training_manager_can_move_and_copy_folders(): void
    {
        Storage::fake('local');

        $manager = User::factory()->trainingManager()->create();
        $library = app(MaterialLibraryService::class);

        $rootA = $library->createFolder($manager, 'A1');
        $rootB = $library->createFolder($manager, 'B1');
        $child = $library->createFolder($manager, 'New Students', $rootA->id);
        $nested = $library->createFolder($manager, 'Week 1', $child->id);

        $file = $library->uploadFile(
            $manager,
            $child,
            UploadedFile::fake()->create('pack.pdf', 50, 'application/pdf'),
        );

        $this->actingAs($manager)
            ->post(route('training-manager.library.folders.move', $child), [
                'parent_id' => $rootB->id,
            ])
            ->assertRedirect(route('training-manager.library.index', ['folder' => $rootA->id]));

        $this->assertSame($rootB->id, $child->fresh()->parent_id);
        $this->assertSame($child->id, $nested->fresh()->parent_id);

        $this->actingAs($manager)
            ->post(route('training-manager.library.folders.copy', $child), [
                'parent_id' => null,
            ])
            ->assertRedirect(route('training-manager.library.index', ['folder' => $rootB->id]));

        $copied = MaterialFolder::query()
            ->whereNull('parent_id')
            ->where('name', 'New Students')
            ->whereKeyNot($child->id)
            ->first();

        $this->assertNotNull($copied);
        $this->assertSame(1, $copied->files()->count());
        $this->assertSame(1, $copied->children()->count());
        $this->assertNotSame($file->path, $copied->files()->first()?->path);
        Storage::disk('local')->assertExists($file->fresh()->path);
        Storage::disk('local')->assertExists($copied->files()->first()->path);
    }

    public function test_folder_cannot_move_into_itself_or_descendant(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $library = app(MaterialLibraryService::class);

        $parent = $library->createFolder($manager, 'Parent');
        $child = $library->createFolder($manager, 'Child', $parent->id);

        $this->actingAs($manager)
            ->from(route('training-manager.library.index'))
            ->post(route('training-manager.library.folders.move', $parent), [
                'parent_id' => $child->id,
            ])
            ->assertSessionHasErrors('parent_id');

        $this->actingAs($manager)
            ->from(route('training-manager.library.index'))
            ->post(route('training-manager.library.folders.move', $parent), [
                'parent_id' => $parent->id,
            ])
            ->assertSessionHasErrors('parent_id');
    }
}
