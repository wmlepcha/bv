<?php

namespace Tests\Feature\Assignments;

use App\Enums\AssignmentStatus;
use App\Models\Assignment;
use App\Models\AssignmentQuestion;
use App\Models\AssignmentSection;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\User;
use App\Services\AssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AssignmentDuplicateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<array<string, mixed>>
     */
    private function worksheetSectionsPayload(): array
    {
        return [
            [
                'label' => 'A',
                'title' => 'MCQ',
                'questions' => [
                    [
                        'type' => 'mcq',
                        'prompt' => 'Pick one',
                        'points' => 1,
                        'options' => ['A', 'B'],
                        'correct_index' => 0,
                    ],
                ],
            ],
            [
                'label' => 'B',
                'title' => 'Fill in',
                'questions' => [
                    [
                        'type' => 'fill_in_blank',
                        'prompt' => 'Ich ___ Anna.',
                        'template' => 'Ich ___ Anna.',
                        'points' => 1,
                        'hints' => ['heißen'],
                        'model_answers' => [['heiße']],
                    ],
                ],
            ],
        ];
    }

    private function createSourceAssignment(User $manager, Batch $batch): Assignment
    {
        $assignment = Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $manager->id,
            'title' => 'Source worksheet',
            'instructions' => 'Original instructions',
        ]);

        $section = AssignmentSection::factory()->create([
            'assignment_id' => $assignment->id,
            'label' => 'A',
        ]);

        AssignmentQuestion::factory()->create([
            'assignment_id' => $assignment->id,
            'section_id' => $section->id,
            'type' => 'mcq',
            'prompt' => 'Original question',
            'points' => 2,
            'config' => ['options' => ['Ja', 'Nein'], 'correct_index' => 0],
        ]);

        AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
        ]);

        return $assignment->fresh();
    }

    public function test_training_manager_can_duplicate_assignment_to_another_batch(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $sourceBatch = Batch::factory()->create();
        $targetBatch = Batch::factory()->create();
        $student = User::factory()->student()->paid()->create();
        BatchEnrollment::factory()->create([
            'batch_id' => $targetBatch->id,
            'student_id' => $student->id,
        ]);

        $source = $this->createSourceAssignment($manager, $sourceBatch);

        $this->actingAs($manager)
            ->post(route('training-manager.assignments.duplicate.store', $source), [
                'batch_id' => $targetBatch->id,
                'title' => 'Copied worksheet',
                'instructions' => 'Copied instructions',
                'target_type' => 'batch',
                'publish' => true,
                'sections' => $this->worksheetSectionsPayload(),
            ])
            ->assertRedirect();

        $duplicate = Assignment::query()
            ->where('title', 'Copied worksheet')
            ->firstOrFail();

        $this->assertNotSame($source->id, $duplicate->id);
        $this->assertSame($targetBatch->id, $duplicate->batch_id);
        $this->assertSame(AssignmentStatus::Published, $duplicate->status);
        $this->assertSame(2, $duplicate->questions()->count());
        $this->assertSame(1, $source->submissions()->count());
        $this->assertSame(0, $duplicate->submissions()->count());

        $service = app(AssignmentService::class);
        $this->assertTrue($service->isVisibleToStudent($duplicate->fresh(['targets']), $student));
    }

    public function test_teacher_duplicate_can_publish_to_target_batch(): void
    {
        $teacher = User::factory()->teacher()->create();
        $sourceBatch = Batch::factory()->create(['teacher_id' => $teacher->id]);
        $targetBatch = Batch::factory()->create(['teacher_id' => $teacher->id]);
        $source = $this->createSourceAssignment($teacher, $sourceBatch);

        $this->actingAs($teacher)
            ->post(route('teacher.assignments.duplicate.store', $source), [
                'batch_id' => $targetBatch->id,
                'title' => 'Teacher copy',
                'target_type' => 'batch',
                'publish' => true,
                'sections' => $this->worksheetSectionsPayload(),
            ])
            ->assertRedirect();

        $duplicate = Assignment::query()->where('title', 'Teacher copy')->firstOrFail();

        $this->assertSame($targetBatch->id, $duplicate->batch_id);
        $this->assertSame(AssignmentStatus::Published, $duplicate->status);
        $this->assertNotNull($duplicate->published_at);
    }

    public function test_teacher_duplicate_can_save_as_draft_on_target_batch(): void
    {
        $teacher = User::factory()->teacher()->create();
        $sourceBatch = Batch::factory()->create(['teacher_id' => $teacher->id]);
        $targetBatch = Batch::factory()->create(['teacher_id' => $teacher->id]);
        $source = $this->createSourceAssignment($teacher, $sourceBatch);

        $this->actingAs($teacher)
            ->post(route('teacher.assignments.duplicate.store', $source), [
                'batch_id' => $targetBatch->id,
                'title' => 'Teacher draft copy',
                'target_type' => 'batch',
                'publish' => false,
                'sections' => $this->worksheetSectionsPayload(),
            ])
            ->assertRedirect();

        $duplicate = Assignment::query()->where('title', 'Teacher draft copy')->firstOrFail();

        $this->assertSame($targetBatch->id, $duplicate->batch_id);
        $this->assertSame(AssignmentStatus::Draft, $duplicate->status);
        $this->assertNull($duplicate->published_at);
    }

    public function test_duplicate_copies_audio_from_source_when_present(): void
    {
        Storage::fake('local');

        $manager = User::factory()->trainingManager()->create();
        $sourceBatch = Batch::factory()->create();
        $targetBatch = Batch::factory()->create();
        $source = $this->createSourceAssignment($manager, $sourceBatch);

        $audioPath = 'assignments/'.$source->id.'/audio/source.mp3';
        Storage::disk('local')->put($audioPath, 'audio-bytes');
        $source->update([
            'audio_disk' => 'local',
            'audio_path' => $audioPath,
            'audio_original_name' => 'listening.mp3',
        ]);

        $this->actingAs($manager)
            ->post(route('training-manager.assignments.duplicate.store', $source), [
                'batch_id' => $targetBatch->id,
                'title' => 'Audio copy',
                'target_type' => 'batch',
                'publish' => true,
                'sections' => $this->worksheetSectionsPayload(),
            ])
            ->assertRedirect();

        $duplicate = Assignment::query()->where('title', 'Audio copy')->firstOrFail();

        $this->assertTrue($duplicate->hasAudio());
        $this->assertSame('listening.mp3', $duplicate->audio_original_name);
        Storage::disk('local')->assertExists((string) $duplicate->audio_path);
    }

    public function test_duplicate_form_renders_prefilled_source(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();
        $source = $this->createSourceAssignment($manager, $batch);

        $this->actingAs($manager)
            ->get(route('training-manager.assignments.duplicate', $source))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/training-manager/assignments/duplicate')
                ->where('source.id', $source->id)
                ->has('batches')
            );
    }

    public function test_duplicate_uses_uploaded_audio_instead_of_copying_source(): void
    {
        Storage::fake('local');

        $manager = User::factory()->trainingManager()->create();
        $sourceBatch = Batch::factory()->create();
        $targetBatch = Batch::factory()->create();
        $source = $this->createSourceAssignment($manager, $sourceBatch);

        $sourcePath = 'assignments/'.$source->id.'/audio/source.mp3';
        Storage::disk('local')->put($sourcePath, 'old-audio');
        $source->update([
            'audio_disk' => 'local',
            'audio_path' => $sourcePath,
            'audio_original_name' => 'old.mp3',
        ]);

        $upload = UploadedFile::fake()->create('new.mp3', 100, 'audio/mpeg');

        $this->actingAs($manager)
            ->post(route('training-manager.assignments.duplicate.store', $source), [
                'batch_id' => $targetBatch->id,
                'title' => 'New audio copy',
                'target_type' => 'batch',
                'publish' => true,
                'sections' => $this->worksheetSectionsPayload(),
                'audio' => $upload,
            ])
            ->assertRedirect();

        $duplicate = Assignment::query()->where('title', 'New audio copy')->firstOrFail();

        $this->assertSame('new.mp3', $duplicate->audio_original_name);
    }
}
