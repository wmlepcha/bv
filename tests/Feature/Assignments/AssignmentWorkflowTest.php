<?php

namespace Tests\Feature\Assignments;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentSubmissionStatus;
use App\Models\Assignment;
use App\Models\AssignmentQuestion;
use App\Models\AssignmentSection;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\User;
use App\Notifications\AssignmentGraded;
use Database\Seeders\AssignmentDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AssignmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_faculty_can_publish_assignment_directly(): void
    {
        $teacher = User::factory()->teacher()->create();
        $batch = Batch::factory()->create(['teacher_id' => $teacher->id]);

        $response = $this->actingAs($teacher)->post(route('teacher.assignments.store'), [
            'batch_id' => $batch->id,
            'title' => 'Test Assignment',
            'instructions' => 'Do the work',
            'target_type' => 'batch',
            'publish' => true,
            'questions' => [
                [
                    'type' => 'mcq',
                    'prompt' => 'Pick one',
                    'points' => 1,
                    'options' => ['A', 'B'],
                    'correct_index' => 0,
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('assignments', [
            'title' => 'Test Assignment',
            'status' => AssignmentStatus::Published->value,
            'created_by' => $teacher->id,
            'approved_by' => $teacher->id,
        ]);
    }

    public function test_faculty_can_save_assignment_as_draft(): void
    {
        $teacher = User::factory()->teacher()->create();
        $batch = Batch::factory()->create(['teacher_id' => $teacher->id]);

        $this->actingAs($teacher)->post(route('teacher.assignments.store'), [
            'batch_id' => $batch->id,
            'title' => 'Faculty Draft',
            'target_type' => 'batch',
            'publish' => false,
            'questions' => [
                [
                    'type' => 'mcq',
                    'prompt' => 'Pick one',
                    'points' => 1,
                    'options' => ['A', 'B'],
                    'correct_index' => 0,
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('assignments', [
            'title' => 'Faculty Draft',
            'status' => AssignmentStatus::Draft->value,
            'created_by' => $teacher->id,
        ]);
    }

    public function test_training_manager_created_assignment_is_published(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();

        $this->actingAs($manager)->post(route('training-manager.assignments.store'), [
            'batch_id' => $batch->id,
            'title' => 'TM Assignment',
            'target_type' => 'batch',
            'questions' => [
                [
                    'type' => 'mcq',
                    'prompt' => 'Q1',
                    'points' => 1,
                    'options' => ['A', 'B'],
                    'correct_index' => 0,
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('assignments', [
            'title' => 'TM Assignment',
            'status' => AssignmentStatus::Published->value,
        ]);
    }

    public function test_training_manager_can_save_assignment_as_draft(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();

        $this->actingAs($manager)->post(route('training-manager.assignments.store'), [
            'batch_id' => $batch->id,
            'title' => 'TM Draft Worksheet',
            'target_type' => 'batch',
            'publish' => false,
            'sections' => [
                [
                    'label' => 'A',
                    'questions' => [
                        [
                            'type' => 'mcq',
                            'prompt' => 'Q1',
                            'points' => 1,
                            'options' => ['A', 'B'],
                            'correct_index' => 0,
                        ],
                    ],
                ],
            ],
        ])->assertRedirect();

        $assignment = Assignment::query()->where('title', 'TM Draft Worksheet')->firstOrFail();

        $this->assertSame(AssignmentStatus::Draft, $assignment->status);
        $this->assertNull($assignment->published_at);
    }

    public function test_training_manager_can_publish_draft_from_edit(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();
        $assignment = Assignment::factory()->create([
            'batch_id' => $batch->id,
            'created_by' => $manager->id,
            'status' => AssignmentStatus::Draft,
            'title' => 'Ready to publish',
        ]);
        $section = AssignmentSection::factory()->create([
            'assignment_id' => $assignment->id,
            'label' => 'A',
        ]);
        AssignmentQuestion::factory()->create([
            'assignment_id' => $assignment->id,
            'section_id' => $section->id,
            'type' => 'mcq',
            'prompt' => 'Q1',
            'points' => 1,
            'config' => ['options' => ['A', 'B'], 'correct_index' => 0],
        ]);

        $this->actingAs($manager)->put(route('training-manager.assignments.update', $assignment), [
            'title' => 'Ready to publish',
            'target_type' => 'batch',
            'publish' => true,
            'sections' => [
                [
                    'label' => 'A',
                    'questions' => [
                        [
                            'type' => 'mcq',
                            'prompt' => 'Q1',
                            'points' => 1,
                            'options' => ['A', 'B'],
                            'correct_index' => 0,
                        ],
                    ],
                ],
            ],
        ])->assertRedirect();

        $assignment->refresh();

        $this->assertSame(AssignmentStatus::Published, $assignment->status);
        $this->assertNotNull($assignment->published_at);
    }

    public function test_faculty_can_publish_draft_from_edit(): void
    {
        $teacher = User::factory()->teacher()->create();
        $batch = Batch::factory()->create(['teacher_id' => $teacher->id]);
        $assignment = Assignment::factory()->create([
            'batch_id' => $batch->id,
            'created_by' => $teacher->id,
            'status' => AssignmentStatus::Draft,
            'title' => 'Faculty ready to publish',
        ]);
        $section = AssignmentSection::factory()->create([
            'assignment_id' => $assignment->id,
            'label' => 'A',
        ]);
        AssignmentQuestion::factory()->create([
            'assignment_id' => $assignment->id,
            'section_id' => $section->id,
            'type' => 'mcq',
            'prompt' => 'Q1',
            'points' => 1,
            'config' => ['options' => ['A', 'B'], 'correct_index' => 0],
        ]);

        $this->actingAs($teacher)->put(route('teacher.assignments.update', $assignment), [
            'title' => 'Faculty ready to publish',
            'target_type' => 'batch',
            'publish' => true,
            'sections' => [
                [
                    'label' => 'A',
                    'questions' => [
                        [
                            'type' => 'mcq',
                            'prompt' => 'Q1',
                            'points' => 1,
                            'options' => ['A', 'B'],
                            'correct_index' => 0,
                        ],
                    ],
                ],
            ],
        ])->assertRedirect();

        $assignment->refresh();

        $this->assertSame(AssignmentStatus::Published, $assignment->status);
        $this->assertNotNull($assignment->published_at);
        $this->assertSame($teacher->id, $assignment->approved_by);
    }

    public function test_training_manager_index_lists_creators_and_hides_other_managers(): void
    {
        $manager = User::factory()->trainingManager()->create(['name' => 'Manager One']);
        $otherManager = User::factory()->trainingManager()->create(['name' => 'Manager Two']);
        $teacher = User::factory()->teacher()->create(['name' => 'Faculty One']);
        $batch = Batch::factory()->create(['teacher_id' => $teacher->id]);

        Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $manager->id,
            'title' => 'My TM assignment',
        ]);
        Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $otherManager->id,
            'title' => 'Other TM assignment',
        ]);
        Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $teacher->id,
            'title' => 'Faculty assignment',
        ]);

        $this->actingAs($manager)
            ->get(route('training-manager.assignments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/training-manager/assignments/index')
                ->where('currentCreator', null)
                ->has('creators', 2)
                ->where('creators.0.name', 'My Assignments')
                ->where('creators.0.is_mine', true)
                ->where('creators.1.name', 'Faculty One')
                ->where('creators.1.is_mine', false)
            );
    }

    public function test_training_manager_index_always_includes_my_assignments_card(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $teacher = User::factory()->teacher()->create(['name' => 'Faculty Only']);
        $batch = Batch::factory()->create(['teacher_id' => $teacher->id]);

        Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $teacher->id,
            'title' => 'Faculty assignment',
        ]);

        $this->actingAs($manager)
            ->get(route('training-manager.assignments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/training-manager/assignments/index')
                ->has('creators', 2)
                ->where('creators.0.name', 'My Assignments')
                ->where('creators.0.is_mine', true)
                ->where('creators.0.assignments_count', 0)
                ->where('creators.1.name', 'Faculty Only')
            );
    }

    public function test_training_manager_can_drill_into_creator_assignments(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $teacher = User::factory()->teacher()->create();
        $batch = Batch::factory()->create(['teacher_id' => $teacher->id]);

        $mine = Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $manager->id,
            'title' => 'Mine published',
            'folder_id' => null,
        ]);
        Assignment::factory()->create([
            'batch_id' => $batch->id,
            'created_by' => $manager->id,
            'title' => 'Mine draft',
            'status' => AssignmentStatus::Draft,
            'folder_id' => null,
        ]);
        Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $teacher->id,
            'title' => 'Faculty published',
            'folder_id' => null,
        ]);

        $this->actingAs($manager)
            ->get(route('training-manager.assignments.index', [
                'creator' => $manager->id,
                'status' => 'published',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/training-manager/assignments/index')
                ->where('currentCreator.id', $manager->id)
                ->where('currentCreator.name', 'My Assignments')
                ->where('currentCreator.is_mine', true)
                ->where('status', 'published')
                ->has('assignments', 1)
                ->where('assignments.0.id', $mine->id)
                ->where('tabCounts.published', 1)
                ->where('tabCounts.draft', 1)
            );
    }

    public function test_training_manager_cannot_view_other_manager_assignment(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $otherManager = User::factory()->trainingManager()->create();
        $assignment = Assignment::factory()->published()->create([
            'created_by' => $otherManager->id,
        ]);

        $this->actingAs($manager)
            ->get(route('training-manager.assignments.show', $assignment))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('training-manager.assignments.edit', $assignment))
            ->assertForbidden();
    }

    public function test_training_manager_can_view_faculty_assignment(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $teacher = User::factory()->teacher()->create();
        $assignment = Assignment::factory()->published()->create([
            'created_by' => $teacher->id,
        ]);

        $this->actingAs($manager)
            ->get(route('training-manager.assignments.show', $assignment))
            ->assertOk();
    }

    public function test_student_can_submit_once(): void
    {
        $student = User::factory()->student()->paid()->create();
        $batch = Batch::factory()->create();
        BatchEnrollment::factory()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        $assignment = Assignment::factory()->published()->create(['batch_id' => $batch->id]);
        $assignment->targets()->create(['scope' => 'batch', 'student_id' => null]);
        $question = $assignment->questions()->create([
            'sort_order' => 0,
            'type' => 'mcq',
            'prompt' => 'Q',
            'points' => 1,
            'config' => ['options' => ['A', 'B'], 'correct_index' => 0],
        ]);

        $payload = [
            'answers' => [
                ['question_id' => $question->id, 'selected_index' => 0],
            ],
        ];

        $this->actingAs($student)
            ->post(route('student.assignments.submit', $assignment), $payload)
            ->assertRedirect();

        $this->actingAs($student)
            ->post(route('student.assignments.submit', $assignment), $payload)
            ->assertSessionHasErrors();
    }

    public function test_training_manager_can_grade_with_score_override(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $assignment = Assignment::factory()->published()->create(['max_score' => 2]);
        $submission = AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'status' => AssignmentSubmissionStatus::Submitted,
            'auto_score' => 2,
        ]);

        $this->actingAs($manager)->put(
            route('training-manager.assignments.submissions.grade.save', [
                'assignment' => $assignment,
                'submission' => $submission,
            ]),
            [
                'final_score' => 1,
                'feedback' => 'Needs improvement',
                'score_overridden' => true,
                'annotations' => [
                    [
                        'type' => 'memo',
                        'question_id' => null,
                        'payload' => ['x' => 10, 'y' => 10, 'text' => 'Check grammar'],
                    ],
                ],
            ],
        )->assertRedirect();

        $submission->refresh();
        $this->assertSame(AssignmentSubmissionStatus::Graded, $submission->status);
        $this->assertSame(1, $submission->final_score);
        $this->assertTrue($submission->score_overridden);
        $this->assertDatabaseHas('submission_annotations', [
            'submission_id' => $submission->id,
            'type' => 'memo',
        ]);
    }

    public function test_grading_notifies_student_by_email_and_in_app(): void
    {
        Notification::fake();

        $manager = User::factory()->trainingManager()->create();
        $assignment = Assignment::factory()->published()->create(['max_score' => 2]);
        $submission = AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'status' => AssignmentSubmissionStatus::Submitted,
            'auto_score' => 2,
        ]);
        $student = $submission->student;

        $this->actingAs($manager)->put(
            route('training-manager.assignments.submissions.grade.save', [
                'assignment' => $assignment,
                'submission' => $submission,
            ]),
            [
                'final_score' => 2,
                'feedback' => 'Well done',
                'score_overridden' => false,
                'annotations' => [],
            ],
        )->assertRedirect();

        Notification::assertSentTo(
            $student,
            AssignmentGraded::class,
            function (AssignmentGraded $notification, array $channels) use ($assignment): bool {
                return in_array('mail', $channels, true)
                    && in_array('database', $channels, true)
                    && $notification->submission->assignment->is($assignment);
            },
        );
    }

    public function test_faculty_can_grade_own_assignment_submission(): void
    {
        $teacher = User::factory()->teacher()->create();
        $batch = Batch::factory()->create(['teacher_id' => $teacher->id]);
        $assignment = Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $teacher->id,
            'max_score' => 2,
        ]);
        $submission = AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'status' => AssignmentSubmissionStatus::Submitted,
            'auto_score' => 2,
        ]);

        $this->actingAs($teacher)
            ->get(route('teacher.assignments.submissions.grade', [
                'assignment' => $assignment,
                'submission' => $submission,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/teacher/assignments/submissions/grade')
            );

        $this->actingAs($teacher)->put(
            route('teacher.assignments.submissions.grade.save', [
                'assignment' => $assignment,
                'submission' => $submission,
            ]),
            [
                'final_score' => 1,
                'feedback' => 'Nice work',
                'score_overridden' => true,
                'annotations' => [],
            ],
        )->assertRedirect(route('teacher.assignments.submissions', $assignment));

        $submission->refresh();
        $this->assertSame(AssignmentSubmissionStatus::Graded, $submission->status);
        $this->assertSame(1, $submission->final_score);
        $this->assertSame($teacher->id, $submission->graded_by);
    }

    public function test_faculty_cannot_grade_training_manager_assignment_on_their_batch(): void
    {
        $teacher = User::factory()->teacher()->create();
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create(['teacher_id' => $teacher->id]);
        $assignment = Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $manager->id,
            'max_score' => 2,
        ]);
        $submission = AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'status' => AssignmentSubmissionStatus::Submitted,
        ]);

        $this->actingAs($teacher)
            ->get(route('teacher.assignments.submissions.grade', [
                'assignment' => $assignment,
                'submission' => $submission,
            ]))
            ->assertForbidden();

        $this->actingAs($teacher)->put(
            route('teacher.assignments.submissions.grade.save', [
                'assignment' => $assignment,
                'submission' => $submission,
            ]),
            [
                'final_score' => 1,
                'feedback' => null,
                'score_overridden' => false,
                'annotations' => [],
            ],
        )->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('teacher.assignments.submissions.show', [
                'assignment' => $assignment,
                'submission' => $submission,
            ]))
            ->assertOk();
    }

    public function test_faculty_cannot_access_training_manager_grade_routes(): void
    {
        $teacher = User::factory()->teacher()->create();
        $batch = Batch::factory()->create(['teacher_id' => $teacher->id]);
        $assignment = Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $teacher->id,
        ]);
        $submission = AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
        ]);

        $this->actingAs($teacher)
            ->get(route('training-manager.assignments.submissions.grade', [
                'assignment' => $assignment,
                'submission' => $submission,
            ]))
            ->assertForbidden();
    }

    public function test_training_manager_can_grade_faculty_assignment(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $teacher = User::factory()->teacher()->create();
        $assignment = Assignment::factory()->published()->create([
            'created_by' => $teacher->id,
            'max_score' => 2,
        ]);
        $submission = AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'status' => AssignmentSubmissionStatus::Submitted,
            'auto_score' => 1,
        ]);

        $this->actingAs($manager)->put(
            route('training-manager.assignments.submissions.grade.save', [
                'assignment' => $assignment,
                'submission' => $submission,
            ]),
            [
                'final_score' => 2,
                'feedback' => 'Good',
                'score_overridden' => true,
                'annotations' => [],
            ],
        )->assertRedirect();

        $this->assertSame(AssignmentSubmissionStatus::Graded, $submission->fresh()->status);
    }

    public function test_training_manager_cannot_grade_other_manager_assignment(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $otherManager = User::factory()->trainingManager()->create();
        $assignment = Assignment::factory()->published()->create([
            'created_by' => $otherManager->id,
            'max_score' => 2,
        ]);
        $submission = AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'status' => AssignmentSubmissionStatus::Submitted,
        ]);

        $this->actingAs($manager)
            ->get(route('training-manager.assignments.submissions.grade', [
                'assignment' => $assignment,
                'submission' => $submission,
            ]))
            ->assertForbidden();

        $this->actingAs($manager)->put(
            route('training-manager.assignments.submissions.grade.save', [
                'assignment' => $assignment,
                'submission' => $submission,
            ]),
            [
                'final_score' => 1,
                'feedback' => null,
                'score_overridden' => false,
                'annotations' => [],
            ],
        )->assertForbidden();
    }

    public function test_unpaid_student_cannot_access_assignments(): void
    {
        $student = User::factory()->student()->unpaid()->create();
        $batch = Batch::factory()->create();
        BatchEnrollment::factory()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);
        $assignment = Assignment::factory()->published()->create(['batch_id' => $batch->id]);
        $assignment->targets()->create(['scope' => 'batch', 'student_id' => null]);

        $this->actingAs($student)
            ->get(route('student.assignments.index'))
            ->assertForbidden();
    }

    public function test_training_manager_assignments_index_accepts_status_filter(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();

        Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $manager->id,
            'title' => 'Published one',
            'folder_id' => null,
        ]);
        Assignment::factory()->create([
            'batch_id' => $batch->id,
            'created_by' => $manager->id,
            'title' => 'Draft one',
            'status' => AssignmentStatus::Draft,
            'folder_id' => null,
        ]);

        $this->actingAs($manager)
            ->get(route('training-manager.assignments.index', [
                'creator' => $manager->id,
                'status' => 'draft',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/training-manager/assignments/index')
                ->where('status', 'draft')
                ->has('assignments', 1)
                ->where('assignments.0.title', 'Draft one')
            );
    }

    public function test_trial_batch_demo_assignment_is_seeded(): void
    {
        $this->seed(AssignmentDemoSeeder::class);

        $batch = Batch::query()->where('name', 'TRIAL BATCH')->first();

        $this->assertNotNull($batch);
        $this->assertSame('A1', $batch->level->value);

        $assignment = Assignment::query()
            ->where('batch_id', $batch->id)
            ->where('title', 'A1 Demo: Begrüßung & Alltag')
            ->first();

        $this->assertNotNull($assignment);
        $this->assertSame(AssignmentStatus::Published, $assignment->status);
        $this->assertSame(7, $assignment->max_score);
        $this->assertSame(4, $assignment->sections()->count());
        $this->assertSame(7, $assignment->questions()->count());
        $this->assertCount(
            4,
            $assignment->questions()
                ->pluck('type')
                ->map(fn ($type) => $type->value)
                ->unique()
                ->values()
                ->all(),
        );
    }
}
