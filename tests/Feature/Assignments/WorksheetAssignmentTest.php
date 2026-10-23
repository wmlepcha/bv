<?php

namespace Tests\Feature\Assignments;

use App\Models\Assignment;
use App\Models\AssignmentQuestion;
use App\Models\AssignmentSection;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WorksheetAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_manager_can_create_worksheet_with_sections(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();

        $this->actingAs($manager)->post(route('training-manager.assignments.store'), [
            'batch_id' => $batch->id,
            'title' => 'A1 Arbeitsblatt',
            'target_type' => 'batch',
            'sections' => [
                [
                    'label' => 'A',
                    'title' => 'Sätze bilden',
                    'instructions' => 'Ordnen Sie die Wörter.',
                    'questions' => [
                        [
                            'type' => 'word_reorder',
                            'prompt' => 'ich / gehe',
                            'points' => 1,
                            'tokens' => ['ich', 'gehe'],
                        ],
                    ],
                ],
                [
                    'label' => 'C',
                    'title' => 'Verben',
                    'questions' => [
                        [
                            'type' => 'fill_in_blank',
                            'prompt' => 'Ich ___ Deutsch.',
                            'template' => 'Ich ___ Deutsch.',
                            'points' => 1,
                            'hints' => ['lernen'],
                        ],
                    ],
                ],
            ],
        ])->assertRedirect();

        $assignment = Assignment::query()->where('title', 'A1 Arbeitsblatt')->firstOrFail();

        $this->assertSame(2, $assignment->sections()->count());
        $this->assertSame(2, $assignment->questions()->count());
        $this->assertDatabaseHas('assignment_questions', [
            'assignment_id' => $assignment->id,
            'type' => 'word_reorder',
        ]);
        $this->assertDatabaseHas('assignment_questions', [
            'assignment_id' => $assignment->id,
            'type' => 'fill_in_blank',
        ]);
    }

    public function test_worksheet_with_sections_ignores_empty_questions_field(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();

        $this->actingAs($manager)->post(route('training-manager.assignments.store'), [
            'batch_id' => $batch->id,
            'title' => 'Section only payload',
            'target_type' => 'batch',
            'questions' => '',
            'sections' => [
                [
                    'label' => 'A',
                    'title' => 'Word order',
                    'questions' => [
                        [
                            'type' => 'word_reorder',
                            'prompt' => 'ich / gehe',
                            'points' => 1,
                            'tokens' => ['ich', 'gehe'],
                        ],
                    ],
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('assignments', [
            'title' => 'Section only payload',
        ]);
    }

    public function test_fill_in_blank_normalizes_underscores_and_stores_model_answers(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();

        $this->actingAs($manager)->post(route('training-manager.assignments.store'), [
            'batch_id' => $batch->id,
            'title' => 'Separable verbs',
            'target_type' => 'batch',
            'sections' => [
                [
                    'label' => 'C',
                    'instructions' => 'Write the missing verbs.',
                    'questions' => [
                        [
                            'type' => 'fill_in_blank',
                            'prompt' => 'Er ____ einen Salat ____ .',
                            'template' => 'Er ____ einen Salat ____ .',
                            'points' => 1,
                            'hints' => ['einkaufen'],
                            'model_answers' => [['kauft'], ['ein']],
                        ],
                    ],
                ],
            ],
        ])->assertRedirect();

        $question = AssignmentQuestion::query()
            ->where('type', 'fill_in_blank')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('Er ___ einen Salat ___ .', $question->config['template']);
        $this->assertSame(2, $question->config['blank_count']);
        $this->assertSame([['kauft'], ['ein']], $question->config['model_answers']);
        $this->assertSame(['einkaufen'], $question->config['hints']);
    }

    public function test_edit_assignment_includes_answer_keys_for_training_manager(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();
        $assignment = Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $manager->id,
        ]);
        $section = AssignmentSection::factory()->create([
            'assignment_id' => $assignment->id,
            'label' => 'E',
        ]);
        AssignmentQuestion::factory()->create([
            'assignment_id' => $assignment->id,
            'section_id' => $section->id,
            'type' => 'fill_in_blank',
            'config' => [
                'template' => 'Ich ___ gut Deutsch sprechen.',
                'hints' => ['können'],
                'blank_count' => 1,
                'model_answers' => [['kann']],
            ],
        ]);

        $this->actingAs($manager)
            ->get(route('training-manager.assignments.edit', $assignment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('assignment.sections.0.questions.0.model_answers', [['kann']])
                ->where('assignment.sections.0.questions.0.hints', ['können'])
            );
    }

    public function test_student_can_submit_fill_in_blank_and_word_reorder_answers(): void
    {
        $student = User::factory()->student()->paid()->create();
        $batch = Batch::factory()->create();
        BatchEnrollment::factory()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        $assignment = Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'max_score' => 2,
        ]);
        $assignment->targets()->create(['scope' => 'batch', 'student_id' => null]);

        $section = AssignmentSection::factory()->create([
            'assignment_id' => $assignment->id,
            'label' => 'A',
        ]);

        $wordQuestion = AssignmentQuestion::factory()->create([
            'assignment_id' => $assignment->id,
            'section_id' => $section->id,
            'sort_order' => 0,
            'type' => 'word_reorder',
            'prompt' => 'ich / gehe',
            'points' => 1,
            'config' => ['tokens' => ['ich', 'gehe']],
        ]);

        $blankQuestion = AssignmentQuestion::factory()->create([
            'assignment_id' => $assignment->id,
            'section_id' => $section->id,
            'sort_order' => 1,
            'type' => 'fill_in_blank',
            'prompt' => 'Ich ___ Deutsch.',
            'points' => 1,
            'config' => [
                'template' => 'Ich ___ Deutsch.',
                'hints' => ['lernen'],
                'blank_count' => 1,
            ],
        ]);

        $this->actingAs($student)->post(route('student.assignments.submit', $assignment), [
            'answers' => [
                [
                    'question_id' => $wordQuestion->id,
                    'ordered_tokens' => ['ich', 'gehe'],
                ],
                [
                    'question_id' => $blankQuestion->id,
                    'blanks' => ['lerne'],
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('assignment_answers', [
            'question_id' => $wordQuestion->id,
        ]);
        $this->assertDatabaseHas('assignment_answers', [
            'question_id' => $blankQuestion->id,
        ]);
    }

    public function test_invalid_word_reorder_tokens_are_rejected(): void
    {
        $student = User::factory()->student()->paid()->create();
        $batch = Batch::factory()->create();
        BatchEnrollment::factory()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        $assignment = Assignment::factory()->published()->create(['batch_id' => $batch->id]);
        $assignment->targets()->create(['scope' => 'batch', 'student_id' => null]);

        $section = AssignmentSection::factory()->create(['assignment_id' => $assignment->id]);
        $question = AssignmentQuestion::factory()->create([
            'assignment_id' => $assignment->id,
            'section_id' => $section->id,
            'type' => 'word_reorder',
            'config' => ['tokens' => ['ich', 'gehe']],
        ]);

        $this->actingAs($student)->post(route('student.assignments.submit', $assignment), [
            'answers' => [
                [
                    'question_id' => $question->id,
                    'ordered_tokens' => ['ich', 'fahre'],
                ],
            ],
        ])->assertSessionHasErrors();
    }

    public function test_student_show_includes_sections(): void
    {
        $student = User::factory()->student()->paid()->create();
        $batch = Batch::factory()->create();
        BatchEnrollment::factory()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        $assignment = Assignment::factory()->published()->create(['batch_id' => $batch->id]);
        $assignment->targets()->create(['scope' => 'batch', 'student_id' => null]);
        $section = AssignmentSection::factory()->create([
            'assignment_id' => $assignment->id,
            'label' => 'A',
        ]);
        AssignmentQuestion::factory()->create([
            'assignment_id' => $assignment->id,
            'section_id' => $section->id,
            'type' => 'fill_in_blank',
            'config' => [
                'template' => 'Ich ___ Deutsch.',
                'blank_count' => 1,
            ],
        ]);

        $this->actingAs($student)
            ->get(route('student.assignments.show', $assignment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('assignment.sections', 1)
                ->where('assignment.sections.0.label', 'A')
            );
    }
}
