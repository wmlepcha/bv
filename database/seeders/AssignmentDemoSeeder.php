<?php

namespace Database\Seeders;

use App\Enums\AssignmentQuestionType;
use App\Enums\AssignmentStatus;
use App\Enums\AssignmentSubmissionStatus;
use App\Enums\AssignmentTargetScope;
use App\Enums\CourseLanguage;
use App\Enums\CourseLevel;
use App\Enums\SubmissionAnnotationType;
use App\Enums\UserRole;
use App\Models\Assignment;
use App\Models\AssignmentAnswer;
use App\Models\AssignmentQuestion;
use App\Models\AssignmentSection;
use App\Models\AssignmentSubmission;
use App\Models\AssignmentTarget;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\Center;
use App\Models\SubmissionAnnotation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class AssignmentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $center = Center::query()->where('slug', 'gangtok')->first()
            ?? Center::query()->firstOrFail();

        $trainingManager = User::query()->updateOrCreate(
            ['email' => 'training-manager@demo.bilingualvitae.com'],
            [
                'name' => 'Demo Training Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRole::TrainingManager,
            ],
        );

        $teacher = User::query()->updateOrCreate(
            ['email' => 'teacher@demo.bilingualvitae.com'],
            [
                'name' => 'Demo Teacher',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRole::Teacher,
            ],
        );

        $students = collect([
            'student1@demo.bilingualvitae.com' => 'Anna Schmidt',
            'student2@demo.bilingualvitae.com' => 'Lukas Meyer',
            'student3@demo.bilingualvitae.com' => 'Sofia Becker',
        ])->map(function (string $name, string $email): User {
            return User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'role' => UserRole::Student,
                    'student_number' => 'SK-COI-2026-'.substr(md5($email), 0, 4),
                    'state_code' => 'SK',
                    'registration_paid_at' => now(),
                    'registration_expires_at' => now()->addYears(2),
                ],
            );
        });

        $batch = Batch::query()->updateOrCreate(
            ['name' => 'German B1 · Morning · Demo'],
            [
                'center_id' => $center->id,
                'language' => CourseLanguage::German,
                'level' => CourseLevel::B1,
                'teacher_id' => $teacher->id,
                'is_active' => true,
                'starts_on' => now()->subWeeks(2)->toDateString(),
                'ends_on' => now()->addWeeks(10)->toDateString(),
            ],
        );

        foreach ($students as $student) {
            BatchEnrollment::query()->updateOrCreate(
                [
                    'batch_id' => $batch->id,
                    'student_id' => $student->id,
                ],
            );
        }

        $this->seedPendingFacultyAssignment($teacher, $batch);
        $this->seedPublishedBatchAssignment($trainingManager, $batch);
        $this->seedIndividualAssignment($teacher, $trainingManager, $batch, $students->first());
        $this->seedListeningAssignment($trainingManager, $batch, $students);
        $this->seedA1WorksheetAssignment($trainingManager, $batch);
        $this->seedTrialBatchAssignment($trainingManager, $teacher, $center);
    }

    private function seedTrialBatchAssignment(User $trainingManager, User $teacher, Center $center): void
    {
        $trialBatch = Batch::query()->firstOrCreate(
            ['name' => 'TRIAL BATCH'],
            [
                'center_id' => $center->id,
                'language' => CourseLanguage::German,
                'level' => CourseLevel::A1,
                'teacher_id' => $teacher->id,
                'is_active' => true,
                'starts_on' => now()->subWeek()->toDateString(),
                'ends_on' => now()->addWeeks(12)->toDateString(),
            ],
        );

        $assignment = Assignment::query()->updateOrCreate(
            [
                'batch_id' => $trialBatch->id,
                'title' => 'A1 Demo: Begrüßung & Alltag',
            ],
            [
                'created_by' => $trainingManager->id,
                'instructions' => 'Willkommen im TRIAL BATCH! Bearbeiten Sie alle Abschnitte. Viel Erfolg!',
                'due_at' => now()->addDays(14),
                'status' => AssignmentStatus::Published,
                'published_at' => now()->subHours(6),
                'approved_by' => $trainingManager->id,
                'approved_at' => now()->subHours(6),
            ],
        );

        $this->syncBatchTarget($assignment);
        $this->syncWorksheetSections($assignment, [
            [
                'label' => 'A',
                'title' => 'Begrüßungen',
                'instructions' => 'Wählen Sie die richtige Antwort.',
                'questions' => [
                    [
                        'type' => AssignmentQuestionType::Mcq,
                        'prompt' => 'Wie sagt man „Good morning“ auf Deutsch?',
                        'points' => 1,
                        'config' => [
                            'options' => ['Guten Morgen', 'Gute Nacht', 'Auf Wiedersehen'],
                            'correct_index' => 0,
                        ],
                    ],
                    [
                        'type' => AssignmentQuestionType::Mcq,
                        'prompt' => 'Was ist eine formelle Begrüßung?',
                        'points' => 1,
                        'config' => [
                            'options' => ['Guten Tag', 'Tschüss', 'Ciao'],
                            'correct_index' => 0,
                        ],
                    ],
                ],
            ],
            [
                'label' => 'B',
                'title' => 'Sich vorstellen',
                'instructions' => 'Schreiben Sie einen kurzen Satz auf Deutsch.',
                'questions' => [
                    [
                        'type' => AssignmentQuestionType::ShortText,
                        'prompt' => 'Stellen Sie sich vor: Wie heißen Sie und woher kommen Sie?',
                        'points' => 1,
                        'config' => null,
                    ],
                ],
            ],
            [
                'label' => 'C',
                'title' => 'Verben ergänzen',
                'instructions' => 'Ergänzen Sie die fehlenden Verben.',
                'questions' => [
                    [
                        'type' => AssignmentQuestionType::FillInBlank,
                        'prompt' => 'Ich ___ Anna.',
                        'points' => 1,
                        'config' => [
                            'template' => 'Ich ___ Anna.',
                            'hints' => ['heißen'],
                            'blank_count' => 1,
                            'model_answers' => [['heiße']],
                        ],
                    ],
                    [
                        'type' => AssignmentQuestionType::FillInBlank,
                        'prompt' => 'Wir ___ aus Deutschland.',
                        'points' => 1,
                        'config' => [
                            'template' => 'Wir ___ aus Deutschland.',
                            'hints' => ['kommen'],
                            'blank_count' => 1,
                            'model_answers' => [['kommen']],
                        ],
                    ],
                ],
            ],
            [
                'label' => 'D',
                'title' => 'Sätze bilden',
                'instructions' => 'Ordnen Sie die Wörter in die richtige Reihenfolge.',
                'questions' => [
                    [
                        'type' => AssignmentQuestionType::WordReorder,
                        'prompt' => 'heiße / ich / Maria',
                        'points' => 1,
                        'config' => [
                            'tokens' => ['heiße', 'ich', 'Maria'],
                            'model_sentence' => 'Ich heiße Maria.',
                        ],
                    ],
                    [
                        'type' => AssignmentQuestionType::WordReorder,
                        'prompt' => 'komme / aus / ich / Berlin',
                        'points' => 1,
                        'config' => [
                            'tokens' => ['komme', 'aus', 'ich', 'Berlin'],
                            'model_sentence' => 'Ich komme aus Berlin.',
                        ],
                    ],
                ],
            ],
        ]);

        $student = BatchEnrollment::query()
            ->where('batch_id', $trialBatch->id)
            ->with('student')
            ->orderBy('id')
            ->first()
            ?->student;

        if ($student === null) {
            return;
        }

        $questions = AssignmentQuestion::query()
            ->where('assignment_id', $assignment->id)
            ->orderBy('sort_order')
            ->get();

        $submission = AssignmentSubmission::query()->updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
            ],
            [
                'status' => AssignmentSubmissionStatus::Submitted,
                'submitted_at' => now()->subHour(),
                'auto_score' => 4,
            ],
        );

        AssignmentAnswer::query()->where('submission_id', $submission->id)->delete();

        foreach ($questions as $question) {
            $answer = match ($question->type) {
                AssignmentQuestionType::Mcq => ['selected_index' => 0],
                AssignmentQuestionType::ShortText => ['text' => 'Ich heiße '.$student->name.' und ich komme aus Deutschland.'],
                AssignmentQuestionType::FillInBlank => [
                    'blanks' => str_contains((string) $question->prompt, 'Anna')
                        ? ['heiße']
                        : ['kommen'],
                ],
                AssignmentQuestionType::WordReorder => [
                    'ordered_tokens' => str_contains((string) $question->prompt, 'Maria')
                        ? ['ich', 'heiße', 'Maria']
                        : ['ich', 'komme', 'aus', 'Berlin'],
                ],
                default => [],
            };

            AssignmentAnswer::query()->create([
                'submission_id' => $submission->id,
                'question_id' => $question->id,
                'answer' => $answer,
                'is_correct' => $question->type !== AssignmentQuestionType::ShortText,
            ]);
        }
    }

    private function seedPendingFacultyAssignment(User $teacher, Batch $batch): void
    {
        $assignment = Assignment::query()->updateOrCreate(
            [
                'batch_id' => $batch->id,
                'title' => 'Artikel üben: der, die, das',
            ],
            [
                'created_by' => $teacher->id,
                'instructions' => 'Wählen Sie die richtigen Artikel.',
                'due_at' => now()->addWeek(),
                'max_score' => 2,
                'status' => AssignmentStatus::PendingApproval,
            ],
        );

        $this->syncBatchTarget($assignment);
        $this->syncMcqQuestions($assignment, [
            ['prompt' => '___ Mann', 'options' => ['der', 'die', 'das'], 'correct_index' => 0],
            ['prompt' => '___ Frau', 'options' => ['der', 'die', 'das'], 'correct_index' => 1],
        ]);
    }

    private function seedPublishedBatchAssignment(User $trainingManager, Batch $batch): void
    {
        $assignment = Assignment::query()->updateOrCreate(
            [
                'batch_id' => $batch->id,
                'title' => 'Woche 1: Begrüßungen',
            ],
            [
                'created_by' => $trainingManager->id,
                'instructions' => 'Beantworten Sie alle Fragen.',
                'due_at' => now()->addDays(10),
                'max_score' => 3,
                'status' => AssignmentStatus::Published,
                'published_at' => now()->subDay(),
                'approved_by' => $trainingManager->id,
                'approved_at' => now()->subDay(),
            ],
        );

        $this->syncBatchTarget($assignment);
        $this->syncWorksheetSections($assignment, [
            [
                'label' => 'General',
                'title' => null,
                'instructions' => null,
                'questions' => [
                    [
                        'type' => AssignmentQuestionType::Mcq,
                        'prompt' => 'Wie heißt du?',
                        'points' => 1,
                        'config' => [
                            'options' => ['Ich heiße Anna.', 'Guten Tag.', 'Auf Wiedersehen.'],
                            'correct_index' => 0,
                        ],
                    ],
                    [
                        'type' => AssignmentQuestionType::Mcq,
                        'prompt' => 'Formelle Begrüßung?',
                        'points' => 1,
                        'config' => [
                            'options' => ['Guten Tag', 'Tschüss', 'Ciao'],
                            'correct_index' => 0,
                        ],
                    ],
                    [
                        'type' => AssignmentQuestionType::ShortText,
                        'prompt' => 'Schreiben Sie einen Satz: Woher kommen Sie?',
                        'points' => 1,
                        'config' => null,
                    ],
                ],
            ],
        ]);
    }

    private function seedIndividualAssignment(User $teacher, User $trainingManager, Batch $batch, User $student): void
    {
        $assignment = Assignment::query()->updateOrCreate(
            [
                'batch_id' => $batch->id,
                'title' => 'Extra practice: Akkusativ',
            ],
            [
                'created_by' => $teacher->id,
                'instructions' => 'Individual practice for selected student.',
                'due_at' => now()->addDays(5),
                'max_score' => 1,
                'status' => AssignmentStatus::Published,
                'published_at' => now()->subHours(12),
                'approved_by' => $trainingManager->id,
                'approved_at' => now()->subHours(12),
            ],
        );

        AssignmentTarget::query()->where('assignment_id', $assignment->id)->delete();
        AssignmentTarget::query()->create([
            'assignment_id' => $assignment->id,
            'scope' => AssignmentTargetScope::Student,
            'student_id' => $student->id,
        ]);

        $this->syncMcqQuestions($assignment, [
            [
                'prompt' => 'Ich sehe ___ Hund.',
                'options' => ['den', 'der', 'dem'],
                'correct_index' => 0,
            ],
        ]);
    }

    /**
     * @param  Collection<int, User>  $students
     */
    private function seedListeningAssignment(User $trainingManager, Batch $batch, $students): void
    {
        $assignment = Assignment::query()->updateOrCreate(
            [
                'batch_id' => $batch->id,
                'title' => 'Hörverstehen: Im Supermarkt',
            ],
            [
                'created_by' => $trainingManager->id,
                'instructions' => 'Hören Sie das Audio und beantworten Sie die Fragen.',
                'due_at' => now()->addDays(7),
                'max_score' => 2,
                'status' => AssignmentStatus::Published,
                'published_at' => now()->subDays(2),
                'approved_by' => $trainingManager->id,
                'approved_at' => now()->subDays(2),
            ],
        );

        $this->syncBatchTarget($assignment);
        $this->syncMcqQuestions($assignment, [
            [
                'prompt' => 'Was kauft die Person?',
                'options' => ['Brot', 'Schuhe', 'Auto'],
                'correct_index' => 0,
            ],
            [
                'prompt' => 'Wo findet die Szene statt?',
                'options' => ['Im Supermarkt', 'Im Park', 'In der Schule'],
                'correct_index' => 0,
            ],
        ]);

        $student1 = $students->values()->get(0);
        $student2 = $students->values()->get(1);

        if ($student1 === null || $student2 === null) {
            return;
        }

        $questions = AssignmentQuestion::query()
            ->where('assignment_id', $assignment->id)
            ->orderBy('sort_order')
            ->get();

        $submission1 = AssignmentSubmission::query()->updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $student1->id,
            ],
            [
                'status' => AssignmentSubmissionStatus::Graded,
                'submitted_at' => now()->subDay(),
                'auto_score' => 1,
                'final_score' => 1,
                'score_overridden' => false,
                'feedback' => 'Solid work — watch accusative case.',
                'graded_by' => $trainingManager->id,
                'graded_at' => now()->subHours(6),
            ],
        );

        AssignmentAnswer::query()->where('submission_id', $submission1->id)->delete();
        SubmissionAnnotation::query()->where('submission_id', $submission1->id)->delete();

        foreach ($questions as $index => $question) {
            AssignmentAnswer::query()->create([
                'submission_id' => $submission1->id,
                'question_id' => $question->id,
                'answer' => ['selected_index' => $index === 0 ? 0 : 1],
                'is_correct' => $index === 0,
            ]);
        }

        SubmissionAnnotation::query()->create([
            'submission_id' => $submission1->id,
            'question_id' => $questions->last()?->id,
            'created_by' => $trainingManager->id,
            'type' => SubmissionAnnotationType::Memo,
            'payload' => [
                'x' => 12,
                'y' => 12,
                'text' => 'Gute Arbeit!',
            ],
        ]);

        $submission2 = AssignmentSubmission::query()->updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $student2->id,
            ],
            [
                'status' => AssignmentSubmissionStatus::Submitted,
                'submitted_at' => now()->subHours(3),
                'auto_score' => 2,
            ],
        );

        AssignmentAnswer::query()->where('submission_id', $submission2->id)->delete();

        foreach ($questions as $question) {
            AssignmentAnswer::query()->create([
                'submission_id' => $submission2->id,
                'question_id' => $question->id,
                'answer' => ['selected_index' => 0],
                'is_correct' => true,
            ]);
        }
    }

    private function syncBatchTarget(Assignment $assignment): void
    {
        AssignmentTarget::query()->where('assignment_id', $assignment->id)->delete();
        AssignmentTarget::query()->create([
            'assignment_id' => $assignment->id,
            'scope' => AssignmentTargetScope::Batch,
            'student_id' => null,
        ]);
    }

    /**
     * @param  list<array{prompt: string, options: list<string>, correct_index: int}>  $questions
     */
    private function syncMcqQuestions(Assignment $assignment, array $questions): void
    {
        $this->syncWorksheetSections($assignment, [
            [
                'label' => 'General',
                'title' => null,
                'instructions' => null,
                'questions' => array_map(fn (array $q): array => [
                    'type' => AssignmentQuestionType::Mcq,
                    'prompt' => $q['prompt'],
                    'points' => 1,
                    'config' => [
                        'options' => $q['options'],
                        'correct_index' => $q['correct_index'],
                    ],
                ], $questions),
            ],
        ]);
    }

    /**
     * @param  list<array{label: string, title?: string|null, instructions?: string|null, questions: list<array<string, mixed>>}>  $sections
     */
    private function syncWorksheetSections(Assignment $assignment, array $sections): void
    {
        // Deleting sections only nulls section_id on their questions; remove the questions too or they linger as orphans.
        AssignmentQuestion::query()->where('assignment_id', $assignment->id)->delete();
        AssignmentSection::query()->where('assignment_id', $assignment->id)->delete();

        $maxScore = 0;

        foreach ($sections as $sectionIndex => $sectionData) {
            $section = AssignmentSection::query()->create([
                'assignment_id' => $assignment->id,
                'label' => $sectionData['label'],
                'title' => $sectionData['title'] ?? null,
                'instructions' => $sectionData['instructions'] ?? null,
                'sort_order' => $sectionIndex,
            ]);

            foreach ($sectionData['questions'] as $questionIndex => $questionData) {
                $points = (int) ($questionData['points'] ?? 1);
                $maxScore += $points;

                AssignmentQuestion::query()->create([
                    'assignment_id' => $assignment->id,
                    'section_id' => $section->id,
                    'sort_order' => $questionIndex,
                    'type' => $questionData['type'],
                    'prompt' => $questionData['prompt'],
                    'points' => $points,
                    'config' => $questionData['config'] ?? null,
                ]);
            }
        }

        $assignment->update(['max_score' => $maxScore]);
    }

    private function seedA1WorksheetAssignment(User $trainingManager, Batch $batch): void
    {
        $assignment = Assignment::query()->updateOrCreate(
            [
                'batch_id' => $batch->id,
                'title' => 'A1 Arbeitsblatt: Sätze bilden',
            ],
            [
                'created_by' => $trainingManager->id,
                'instructions' => 'Bearbeiten Sie alle Abschnitte.',
                'due_at' => now()->addDays(14),
                'max_score' => 5,
                'status' => AssignmentStatus::Published,
                'published_at' => now(),
                'approved_by' => $trainingManager->id,
                'approved_at' => now(),
            ],
        );

        $this->syncBatchTarget($assignment);
        $this->syncWorksheetSections($assignment, [
            [
                'label' => 'A',
                'title' => 'Ergänzen Sie die Wörter in die richtige Reihenfolge',
                'instructions' => 'Bilden Sie Sätze.',
                'questions' => [
                    [
                        'type' => AssignmentQuestionType::WordReorder,
                        'prompt' => 'gehe / ich / in / den / Supermarkt / gern',
                        'points' => 1,
                        'config' => [
                            'tokens' => ['gehe', 'ich', 'in', 'den', 'Supermarkt', 'gern'],
                            'model_sentence' => 'Ich gehe gern in den Supermarkt.',
                        ],
                    ],
                    [
                        'type' => AssignmentQuestionType::WordReorder,
                        'prompt' => 'komme / aus / ich / Deutschland',
                        'points' => 1,
                        'config' => [
                            'tokens' => ['komme', 'aus', 'ich', 'Deutschland'],
                            'model_sentence' => 'Ich komme aus Deutschland.',
                        ],
                    ],
                ],
            ],
            [
                'label' => 'C',
                'title' => 'Ergänzen Sie die Verben',
                'instructions' => null,
                'questions' => [
                    [
                        'type' => AssignmentQuestionType::FillInBlank,
                        'prompt' => 'Ich ___ jeden Tag Deutsch.',
                        'points' => 1,
                        'config' => [
                            'template' => 'Ich ___ jeden Tag Deutsch.',
                            'hints' => ['lernen'],
                            'blank_count' => 1,
                            'model_answers' => [['lerne']],
                        ],
                    ],
                    [
                        'type' => AssignmentQuestionType::FillInBlank,
                        'prompt' => 'Wir ___ in Berlin.',
                        'points' => 1,
                        'config' => [
                            'template' => 'Wir ___ in Berlin.',
                            'hints' => ['wohnen'],
                            'blank_count' => 1,
                            'model_answers' => [['wohnen']],
                        ],
                    ],
                ],
            ],
            [
                'label' => 'D',
                'title' => 'Weitere Sätze',
                'instructions' => null,
                'questions' => [
                    [
                        'type' => AssignmentQuestionType::WordReorder,
                        'prompt' => 'trinke / ich / Kaffee / gern',
                        'points' => 1,
                        'config' => [
                            'tokens' => ['trinke', 'ich', 'Kaffee', 'gern'],
                            'model_sentence' => 'Ich trinke gern Kaffee.',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
