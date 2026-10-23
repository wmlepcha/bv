<?php

namespace Tests\Feature\Assignments;

use App\Enums\AssignmentSubmissionStatus;
use App\Models\Assignment;
use App\Models\AssignmentQuestion;
use App\Models\AssignmentSection;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GradingAnnotationTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_manager_can_save_colored_annotations_with_pen_and_memo_payload(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();
        $assignment = Assignment::factory()->published()->create(['batch_id' => $batch->id]);
        $section = AssignmentSection::factory()->create(['assignment_id' => $assignment->id]);
        $question = AssignmentQuestion::factory()->create([
            'assignment_id' => $assignment->id,
            'section_id' => $section->id,
        ]);
        $submission = AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'status' => AssignmentSubmissionStatus::Submitted,
        ]);

        $this->actingAs($manager)->put(
            route('training-manager.assignments.submissions.grade.save', [
                'assignment' => $assignment,
                'submission' => $submission,
            ]),
            [
                'final_score' => 3,
                'feedback' => 'Good effort',
                'score_overridden' => false,
                'annotations' => [
                    [
                        'type' => 'pen',
                        'question_id' => $question->id,
                        'payload' => [
                            'path' => 'M0 0 L10 10',
                            'color' => '#2563EB',
                            'strokeWidth' => 4,
                        ],
                    ],
                    [
                        'type' => 'highlight',
                        'question_id' => $question->id,
                        'payload' => [
                            'start_offset' => 0,
                            'end_offset' => 3,
                            'color' => '#FFE066',
                        ],
                    ],
                    [
                        'type' => 'memo',
                        'question_id' => $question->id,
                        'payload' => [
                            'x' => 24,
                            'y' => 40,
                            'width' => 200,
                            'text' => 'Check word order',
                            'color' => 'pink',
                        ],
                    ],
                ],
            ],
        )->assertRedirect();

        $this->assertDatabaseHas('submission_annotations', [
            'submission_id' => $submission->id,
            'type' => 'pen',
            'question_id' => $question->id,
        ]);

        $pen = $submission->fresh()->annotations()->where('type', 'pen')->first();
        $this->assertSame('#2563EB', $pen->payload['color']);
        $this->assertSame(4, $pen->payload['strokeWidth']);

        $memo = $submission->fresh()->annotations()->where('type', 'memo')->first();
        $this->assertSame('pink', $memo->payload['color']);
        $this->assertSame(200, $memo->payload['width']);
    }

    public function test_grade_page_returns_saved_annotation_payload(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $assignment = Assignment::factory()->published()->create();
        $submission = AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'status' => AssignmentSubmissionStatus::Submitted,
        ]);

        $submission->annotations()->create([
            'created_by' => $manager->id,
            'type' => 'memo',
            'question_id' => null,
            'payload' => [
                'x' => 10,
                'y' => 20,
                'width' => 180,
                'text' => 'Saved memo',
                'color' => 'lavender',
            ],
        ]);

        $this->actingAs($manager)
            ->get(route('training-manager.assignments.submissions.grade', [
                'assignment' => $assignment,
                'submission' => $submission,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/training-manager/assignments/submissions/grade')
                ->has('submission.annotations', 1)
                ->where('submission.annotations.0.payload.color', 'lavender')
                ->where('submission.annotations.0.payload.width', 180)
            );
    }
}
