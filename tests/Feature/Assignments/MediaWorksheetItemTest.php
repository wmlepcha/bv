<?php

namespace Tests\Feature\Assignments;

use App\Enums\AssignmentQuestionType;
use App\Enums\AssignmentSubmissionStatus;
use App\Models\Assignment;
use App\Models\AssignmentAnswer;
use App\Models\AssignmentQuestion;
use App\Models\AssignmentSection;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaWorksheetItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_manager_can_create_assignment_with_media_item_types(): void
    {
        Storage::fake('local');

        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();

        $pdf = UploadedFile::fake()->create('reading.pdf', 200, 'application/pdf');
        $image = UploadedFile::fake()->image('scene.jpg');
        $audio = UploadedFile::fake()->create('listen.mp3', 100, 'audio/mpeg');

        $this->actingAs($manager)->post(route('training-manager.assignments.store'), [
            'batch_id' => $batch->id,
            'title' => 'Media worksheet',
            'target_type' => 'batch',
            'publish' => 1,
            'sections' => [
                [
                    'label' => 'A',
                    'questions' => [
                        [
                            'type' => 'pdf',
                            'prompt' => 'Read the PDF and summarize.',
                            'points' => 2,
                            'media' => $pdf,
                        ],
                        [
                            'type' => 'image',
                            'prompt' => 'Describe the image.',
                            'points' => 1,
                            'media' => $image,
                        ],
                        [
                            'type' => 'audio',
                            'prompt' => 'Listen and answer.',
                            'points' => 1,
                            'media' => $audio,
                        ],
                        [
                            'type' => 'link',
                            'prompt' => 'Open the site and write notes.',
                            'points' => 1,
                            'url' => 'https://example.com/lesson',
                        ],
                    ],
                ],
            ],
        ])->assertRedirect();

        $assignment = Assignment::query()->where('title', 'Media worksheet')->firstOrFail();

        $this->assertSame(5, $assignment->max_score);
        $this->assertSame(4, $assignment->questions()->count());

        $pdfQuestion = $assignment->questions()->where('type', AssignmentQuestionType::Pdf)->firstOrFail();
        $this->assertTrue($pdfQuestion->hasMedia());
        Storage::disk('local')->assertExists((string) $pdfQuestion->mediaPath());

        $linkQuestion = $assignment->questions()->where('type', AssignmentQuestionType::Link)->firstOrFail();
        $this->assertSame('https://example.com/lesson', $linkQuestion->mediaUrl());
    }

    public function test_update_keeps_existing_media_and_prunes_replaced_file(): void
    {
        Storage::fake('local');

        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();
        $assignment = Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $manager->id,
            'max_score' => 1,
        ]);

        $section = AssignmentSection::factory()->create([
            'assignment_id' => $assignment->id,
            'label' => 'A',
        ]);

        $oldPath = 'assignments/'.$assignment->id.'/questions/old.pdf';
        Storage::disk('local')->put($oldPath, 'old-pdf-content');

        AssignmentQuestion::factory()->pdf([
            'path' => $oldPath,
            'original_name' => 'old.pdf',
        ])->create([
            'assignment_id' => $assignment->id,
            'section_id' => $section->id,
            'points' => 1,
        ]);

        $replacement = UploadedFile::fake()->create('new.pdf', 120, 'application/pdf');

        $this->actingAs($manager)->post(route('training-manager.assignments.update', $assignment), [
            '_method' => 'put',
            'title' => $assignment->title,
            'target_type' => 'batch',
            'sections' => [
                [
                    'label' => 'A',
                    'questions' => [
                        [
                            'type' => 'pdf',
                            'prompt' => 'Read the PDF and summarize.',
                            'points' => 1,
                            'media' => $replacement,
                        ],
                    ],
                ],
            ],
        ])->assertRedirect();

        $question = $assignment->fresh(['questions'])->questions()->firstOrFail();
        $this->assertNotSame($oldPath, $question->mediaPath());
        Storage::disk('local')->assertExists((string) $question->mediaPath());
        Storage::disk('local')->assertMissing($oldPath);
    }

    public function test_update_keeps_existing_media_when_no_new_file_uploaded(): void
    {
        Storage::fake('local');

        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();
        $assignment = Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $manager->id,
            'max_score' => 1,
        ]);

        $section = AssignmentSection::factory()->create([
            'assignment_id' => $assignment->id,
            'label' => 'A',
        ]);

        $path = 'assignments/'.$assignment->id.'/questions/keep.pdf';
        Storage::disk('local')->put($path, 'keep-me');

        AssignmentQuestion::factory()->pdf([
            'path' => $path,
            'original_name' => 'keep.pdf',
        ])->create([
            'assignment_id' => $assignment->id,
            'section_id' => $section->id,
            'points' => 2,
            'prompt' => 'Old instructions',
        ]);

        $response = $this->actingAs($manager)->post(route('training-manager.assignments.update', $assignment), [
            '_method' => 'put',
            'title' => $assignment->title,
            'target_type' => 'batch',
            'sections' => [
                [
                    'label' => 'A',
                    'questions' => [
                        [
                            'type' => 'pdf',
                            'prompt' => 'Updated instructions',
                            'points' => 2,
                            'existing_media_path' => $path,
                            'media_original_name' => 'keep.pdf',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $question = $assignment->fresh(['questions'])->questions()->firstOrFail();
        $this->assertSame($path, $question->mediaPath());
        $this->assertSame('Updated instructions', $question->prompt);
        Storage::disk('local')->assertExists($path);
    }

    public function test_student_can_stream_media_and_submit_text_answer(): void
    {
        Storage::fake('local');

        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();
        $student = User::factory()->student()->paid()->create();
        BatchEnrollment::factory()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        $assignment = Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $manager->id,
            'max_score' => 2,
        ]);

        $section = AssignmentSection::factory()->create([
            'assignment_id' => $assignment->id,
            'label' => 'A',
        ]);

        $path = 'assignments/'.$assignment->id.'/questions/reading.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 test');

        $question = AssignmentQuestion::factory()->pdf([
            'path' => $path,
            'original_name' => 'reading.pdf',
        ])->create([
            'assignment_id' => $assignment->id,
            'section_id' => $section->id,
            'points' => 2,
        ]);

        $this->actingAs($student)
            ->get(route('student.assignments.questions.media', [$assignment, $question]))
            ->assertOk();

        $this->actingAs($student)->post(route('student.assignments.submit', $assignment), [
            'answers' => [
                [
                    'question_id' => $question->id,
                    'text' => 'Die Geschichte handelt von einer Familie.',
                ],
            ],
        ])->assertRedirect();

        $submission = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        $this->assertSame(AssignmentSubmissionStatus::Submitted, $submission->status);

        $answer = AssignmentAnswer::query()
            ->where('submission_id', $submission->id)
            ->where('question_id', $question->id)
            ->firstOrFail();

        $this->assertSame('Die Geschichte handelt von einer Familie.', $answer->answer['text'] ?? null);
        $this->assertNull($answer->is_correct);
    }

    public function test_unauthorized_user_cannot_stream_question_media(): void
    {
        Storage::fake('local');

        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();
        $outsider = User::factory()->student()->paid()->create();

        $assignment = Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $manager->id,
        ]);

        $section = AssignmentSection::factory()->create([
            'assignment_id' => $assignment->id,
        ]);

        $path = 'assignments/'.$assignment->id.'/questions/secret.pdf';
        Storage::disk('local')->put($path, 'secret');

        $question = AssignmentQuestion::factory()->pdf([
            'path' => $path,
        ])->create([
            'assignment_id' => $assignment->id,
            'section_id' => $section->id,
        ]);

        $this->actingAs($outsider)
            ->get(route('student.assignments.questions.media', [$assignment, $question]))
            ->assertForbidden();
    }

    public function test_duplicate_copies_question_media_files(): void
    {
        Storage::fake('local');

        $manager = User::factory()->trainingManager()->create();
        $sourceBatch = Batch::factory()->create();
        $targetBatch = Batch::factory()->create();

        $source = Assignment::factory()->published()->create([
            'batch_id' => $sourceBatch->id,
            'created_by' => $manager->id,
            'title' => 'Source media',
            'max_score' => 1,
        ]);

        $section = AssignmentSection::factory()->create([
            'assignment_id' => $source->id,
            'label' => 'A',
        ]);

        $sourcePath = 'assignments/'.$source->id.'/questions/source.pdf';
        Storage::disk('local')->put($sourcePath, 'source-bytes');

        AssignmentQuestion::factory()->pdf([
            'path' => $sourcePath,
            'original_name' => 'source.pdf',
        ])->create([
            'assignment_id' => $source->id,
            'section_id' => $section->id,
            'points' => 1,
            'prompt' => 'Read and answer.',
        ]);

        $this->actingAs($manager)->post(route('training-manager.assignments.duplicate.store', $source), [
            'batch_id' => $targetBatch->id,
            'title' => 'Duplicated media',
            'target_type' => 'batch',
            'publish' => 1,
            'sections' => [
                [
                    'label' => 'A',
                    'questions' => [
                        [
                            'type' => 'pdf',
                            'prompt' => 'Read and answer.',
                            'points' => 1,
                            'existing_media_path' => $sourcePath,
                            'media_original_name' => 'source.pdf',
                        ],
                    ],
                ],
            ],
        ])->assertRedirect();

        $duplicate = Assignment::query()->where('title', 'Duplicated media')->firstOrFail();
        $question = $duplicate->questions()->firstOrFail();

        $this->assertNotSame($sourcePath, $question->mediaPath());
        $this->assertSame('source.pdf', $question->mediaOriginalName());
        Storage::disk('local')->assertExists((string) $question->mediaPath());
        Storage::disk('local')->assertExists($sourcePath);
    }
}
