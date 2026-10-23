<?php

namespace Tests\Feature\Assignments;

use App\Enums\AssignmentQuestionType;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssignmentUploadErrorTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_too_large_on_assignment_route_returns_assignment_errors(): void
    {
        $request = Request::create('/training-manager/assignments', 'POST');
        $request->headers->set('X-Inertia', 'true');

        $response = app(ExceptionHandler::class)
            ->render($request, new PostTooLargeException);

        $this->assertSame(422, $response->getStatusCode());

        $payload = json_decode($response->getContent(), true);

        $this->assertIsArray($payload);
        $this->assertStringContainsString('20MB', $payload['message'] ?? '');
        $this->assertArrayHasKey('audio', $payload['errors']);
        $this->assertArrayHasKey('sections', $payload['errors']);
        $this->assertIsArray($payload['errors']['audio']);
        $this->assertIsArray($payload['errors']['sections']);
        $this->assertArrayNotHasKey('passport_photo', $payload['errors']);
    }

    public function test_post_too_large_on_cv_route_keeps_cv_errors(): void
    {
        $request = Request::create('/student/cv', 'POST');
        $request->headers->set('X-Inertia', 'true');

        $response = app(ExceptionHandler::class)
            ->render($request, new PostTooLargeException);

        $this->assertSame(422, $response->getStatusCode());

        $payload = json_decode($response->getContent(), true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('passport_photo', $payload['errors']);
        $this->assertIsArray($payload['errors']['passport_photo']);
        $this->assertArrayHasKey('passport_document', $payload['errors']);
        $this->assertStringContainsString('passport photo', $payload['message'] ?? '');
    }

    public function test_multipart_mcq_with_media_succeeds_via_sections_json(): void
    {
        Storage::fake('local');

        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();
        $pdf = UploadedFile::fake()->create('reading.pdf', 200, 'application/pdf');

        $sectionsJson = json_encode([
            [
                'label' => 'A',
                'title' => null,
                'instructions' => null,
                'questions' => [
                    [
                        'type' => 'mcq',
                        'prompt' => 'Pick one',
                        'points' => 1,
                        'options' => ['Yes', 'No'],
                        'correct_index' => 0,
                    ],
                    [
                        'type' => 'pdf',
                        'prompt' => 'Read this PDF',
                        'points' => 2,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($manager)->post(route('training-manager.assignments.store'), [
            'batch_id' => $batch->id,
            'title' => 'MCQ with PDF',
            'target_type' => 'batch',
            'publish' => 1,
            'sections_json' => $sectionsJson,
            'sections' => [
                [
                    'questions' => [
                        [],
                        ['media' => $pdf],
                    ],
                ],
            ],
        ])->assertRedirect();

        $assignment = Assignment::query()->where('title', 'MCQ with PDF')->firstOrFail();

        $this->assertSame(2, $assignment->questions()->count());
        $this->assertSame(
            AssignmentQuestionType::Mcq,
            $assignment->questions()->orderBy('sort_order')->firstOrFail()->type,
        );
        $this->assertTrue(
            $assignment->questions()->where('type', AssignmentQuestionType::Pdf)->firstOrFail()->hasMedia(),
        );
    }

    public function test_invalid_options_payload_returns_friendly_message(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();

        $response = $this->actingAs($manager)->post(route('training-manager.assignments.store'), [
            'batch_id' => $batch->id,
            'title' => 'Bad options',
            'target_type' => 'batch',
            'publish' => 0,
            'sections' => [
                [
                    'label' => 'A',
                    'questions' => [
                        [
                            'type' => 'mcq',
                            'prompt' => 'Pick one',
                            'points' => 1,
                            'options' => 'not-an-array',
                            'correct_index' => 0,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertSessionHasErrors();

        $messages = session('errors')->getBag('default')->all();
        $flat = collect($messages)->flatten()->implode(' ');

        $this->assertStringContainsString(
            'Multiple-choice questions need at least two answer options.',
            $flat,
        );
        $this->assertStringNotContainsString('sections.0.questions.0.options field must be an array', $flat);
    }

    public function test_oversized_media_under_php_limit_returns_clear_message(): void
    {
        Storage::fake('local');

        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();
        $pdf = UploadedFile::fake()->create('huge.pdf', 21 * 1024, 'application/pdf');

        $response = $this->actingAs($manager)->post(route('training-manager.assignments.store'), [
            'batch_id' => $batch->id,
            'title' => 'Huge PDF',
            'target_type' => 'batch',
            'publish' => 0,
            'sections' => [
                [
                    'label' => 'A',
                    'questions' => [
                        [
                            'type' => 'pdf',
                            'prompt' => 'Read this',
                            'points' => 1,
                            'media' => $pdf,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertSessionHasErrors();

        $flat = collect(session('errors')->getBag('default')->all())->flatten()->implode(' ');

        $this->assertTrue(
            str_contains($flat, '20MB') || str_contains($flat, '20480'),
            "Expected a clear size error, got: {$flat}",
        );
    }
}
