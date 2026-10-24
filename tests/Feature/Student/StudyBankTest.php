<?php

namespace Tests\Feature\Student;

use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\MaterialFolder;
use App\Models\User;
use App\Notifications\MaterialSharedWithBatch;
use App\Services\MaterialAssignmentService;
use App\Services\MaterialLibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudyBankTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_student_sees_folder_shared_with_their_batch(): void
    {
        Notification::fake();

        [$student, $batch, $folder] = $this->sharedFolderForStudent();

        $this->actingAs($student)
            ->get(route('student.study-bank.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/student/study-bank/index')
                ->where('enrolled', true)
                ->has('folders', 1)
                ->where('folders.0.id', $folder->id)
                ->where('folders.0.name', 'A1 - German')
            );
    }

    public function test_other_batch_student_does_not_see_shared_folder(): void
    {
        Notification::fake();

        [$student] = $this->sharedFolderForStudent();

        $otherBatch = Batch::factory()->create();
        $outsider = User::factory()->student()->paid()->create();
        BatchEnrollment::factory()->create([
            'batch_id' => $otherBatch->id,
            'student_id' => $outsider->id,
        ]);

        $this->actingAs($outsider)
            ->get(route('student.study-bank.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('enrolled', true)
                ->has('folders', 0)
                ->has('files', 0)
            );

        $this->actingAs($student)
            ->get(route('student.study-bank.index'))
            ->assertInertia(fn (Assert $page) => $page->has('folders', 1));
    }

    public function test_unenrolled_student_sees_empty_study_bank(): void
    {
        $student = User::factory()->student()->paid()->create();

        $this->actingAs($student)
            ->get(route('student.study-bank.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('enrolled', false)
                ->has('folders', 0)
            );
    }

    public function test_unpaid_student_cannot_access_study_bank(): void
    {
        $student = User::factory()->student()->unpaid()->create();

        $this->actingAs($student)
            ->get(route('student.study-bank.index'))
            ->assertForbidden();
    }

    public function test_student_can_open_shared_folder_and_see_children(): void
    {
        Notification::fake();
        Storage::fake('local');

        [$student, $batch, $folder, $manager] = $this->sharedFolderForStudent();

        $library = app(MaterialLibraryService::class);
        $child = $library->createFolder($manager, 'Grammar', $folder->id);
        $file = $library->uploadFile(
            $manager,
            $folder,
            UploadedFile::fake()->create('lesson.pdf', 100, 'application/pdf'),
        );

        $this->actingAs($student)
            ->get(route('student.study-bank.folders.show', $folder))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/student/study-bank/folder')
                ->where('folder.id', $folder->id)
                ->has('folders', 1)
                ->where('folders.0.id', $child->id)
                ->has('files', 1)
                ->where('files.0.id', $file->id)
            );
    }

    public function test_student_cannot_open_unshared_folder(): void
    {
        Notification::fake();
        Storage::fake('local');

        $manager = User::factory()->trainingManager()->create();
        $student = User::factory()->student()->paid()->create();
        $batch = Batch::factory()->create();
        BatchEnrollment::factory()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        $hidden = app(MaterialLibraryService::class)->createFolder($manager, 'Hidden');

        $this->actingAs($student)
            ->get(route('student.study-bank.folders.show', $hidden))
            ->assertForbidden();
    }

    public function test_student_cannot_download_unshared_file(): void
    {
        Notification::fake();
        Storage::fake('local');

        $manager = User::factory()->trainingManager()->create();
        $student = User::factory()->student()->paid()->create();
        $batch = Batch::factory()->create();
        BatchEnrollment::factory()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        $library = app(MaterialLibraryService::class);
        $folder = $library->createFolder($manager, 'Private');
        $file = $library->uploadFile(
            $manager,
            $folder,
            UploadedFile::fake()->create('secret.pdf', 100, 'application/pdf'),
        );

        $this->actingAs($student)
            ->get(route('student.study-bank.files.download', $file))
            ->assertForbidden();
    }

    public function test_student_cannot_preview_unshared_file(): void
    {
        Notification::fake();
        Storage::fake('local');

        $manager = User::factory()->trainingManager()->create();
        $student = User::factory()->student()->paid()->create();
        $batch = Batch::factory()->create();
        BatchEnrollment::factory()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        $library = app(MaterialLibraryService::class);
        $folder = $library->createFolder($manager, 'Private');
        $file = $library->uploadFile(
            $manager,
            $folder,
            UploadedFile::fake()->create('secret.pdf', 100, 'application/pdf'),
        );

        $this->actingAs($student)
            ->get(route('student.study-bank.files.preview', $file))
            ->assertForbidden();
    }

    public function test_student_can_preview_shared_pdf_inline(): void
    {
        Notification::fake();
        Storage::fake('local');

        [$student, $batch, $folder, $manager] = $this->sharedFolderForStudent();

        $library = app(MaterialLibraryService::class);
        $file = $library->uploadFile(
            $manager,
            $folder,
            UploadedFile::fake()->create('lesson.pdf', 100, 'application/pdf'),
        );

        $response = $this->actingAs($student)
            ->get(route('student.study-bank.files.preview', $file))
            ->assertOk();

        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
    }

    public function test_student_can_stream_shared_mp3_with_range_support(): void
    {
        Notification::fake();
        Storage::fake('local');

        [$student, , $folder, $manager] = $this->sharedFolderForStudent();

        $file = app(MaterialLibraryService::class)->uploadFile(
            $manager,
            $folder,
            UploadedFile::fake()->createWithContent('lesson.mp3', str_repeat('a', 2048)),
        );

        $this->actingAs($student)
            ->get(route('student.study-bank.files.preview', $file), ['Range' => 'bytes=0-9'])
            ->assertStatus(206);
    }

    public function test_library_accepts_mp3_and_mp4_but_rejects_wav(): void
    {
        Storage::fake('local');

        [, , $folder, $manager] = $this->sharedFolderForStudent();
        $library = app(MaterialLibraryService::class);

        $library->uploadFile($manager, $folder, UploadedFile::fake()->create('a.mp3', 10, 'audio/mpeg'));
        $library->uploadFile($manager, $folder, UploadedFile::fake()->create('b.mp4', 10, 'video/mp4'));

        $this->expectException(ValidationException::class);
        $library->uploadFile($manager, $folder, UploadedFile::fake()->create('c.wav', 10, 'audio/wav'));
    }

    public function test_student_can_download_file_inside_shared_ancestor_folder(): void
    {
        Notification::fake();
        Storage::fake('local');

        [$student, $batch, $folder, $manager] = $this->sharedFolderForStudent();

        $library = app(MaterialLibraryService::class);
        $child = $library->createFolder($manager, 'Worksheets', $folder->id);
        $file = $library->uploadFile(
            $manager,
            $child,
            UploadedFile::fake()->create('worksheet.pdf', 100, 'application/pdf'),
        );

        $this->actingAs($student)
            ->get(route('student.study-bank.files.download', $file))
            ->assertOk();
    }

    public function test_legacy_pdfs_url_redirects_to_study_bank(): void
    {
        $student = User::factory()->student()->paid()->create();

        $this->actingAs($student)
            ->get(route('student.pdfs.index'))
            ->assertRedirect('/student/study-bank');
    }

    public function test_material_shared_notification_points_to_study_bank(): void
    {
        $student = User::factory()->student()->paid()->create();
        $notification = new MaterialSharedWithBatch('A1 - German', 'TRIAL BATCH', 'folder');

        $payload = $notification->toArray($student);

        $this->assertStringContainsString('/student/study-bank', $payload['url']);
        $this->assertStringNotContainsString('/student/pdfs', $payload['url']);
    }

    /**
     * @return array{0: User, 1: Batch, 2: MaterialFolder, 3: User}
     */
    private function sharedFolderForStudent(): array
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create(['is_active' => true]);
        $student = User::factory()->student()->paid()->create();
        BatchEnrollment::factory()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        $folder = app(MaterialLibraryService::class)->createFolder($manager, 'A1 - German');
        app(MaterialAssignmentService::class)->shareFolderWithBatches($manager, $folder, [$batch->id]);

        return [$student, $batch, $folder, $manager];
    }
}
