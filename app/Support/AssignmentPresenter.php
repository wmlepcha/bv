<?php

namespace App\Support;

use App\Enums\AssignmentSubmissionStatus;
use App\Enums\AssignmentTargetScope;
use App\Models\Assignment;
use App\Models\AssignmentQuestion;
use App\Models\AssignmentSection;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\User;
use App\Services\AssignmentService;

class AssignmentPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function assignment(
        Assignment $assignment,
        bool $includeQuestions = true,
        bool $includeAnswerKey = false,
    ): array {
        $assignment->loadMissing(['batch.center', 'creator', 'targets', 'submissions']);

        $targetType = AssignmentTargetScope::Batch->value;
        $studentIds = [];

        if ($assignment->targets->contains(fn ($t) => $t->scope === AssignmentTargetScope::Student)) {
            $targetType = AssignmentTargetScope::Student->value;
            $studentIds = $assignment->targets
                ->where('scope', AssignmentTargetScope::Student)
                ->pluck('student_id')
                ->values()
                ->all();
        }

        $data = [
            'id' => $assignment->id,
            'batch_id' => $assignment->batch_id,
            'folder_id' => $assignment->folder_id,
            'batch' => [
                'id' => $assignment->batch->id,
                'name' => $assignment->batch->name,
                'language' => $assignment->batch->language->value,
                'level' => $assignment->batch->level->value,
            ],
            'title' => $assignment->title,
            'instructions' => $assignment->instructions,
            'due_at' => $assignment->due_at?->toIso8601String(),
            'max_score' => $assignment->max_score,
            'status' => $assignment->status->value,
            'status_label' => $assignment->status->label(),
            'target_type' => $targetType,
            'student_ids' => $studentIds,
            'has_audio' => $assignment->hasAudio(),
            'audio_original_name' => $assignment->audio_original_name,
            'published_at' => $assignment->published_at?->toIso8601String(),
            'rejection_reason' => $assignment->rejection_reason,
            'created_by' => [
                'id' => $assignment->creator->id,
                'name' => $assignment->creator->name,
            ],
            'submissions_count' => $assignment->submissions->count(),
            'created_at' => $assignment->created_at?->toIso8601String(),
        ];

        $workSummary = self::studentWorkSummary($assignment);
        $data['turned_in_count'] = $workSummary['turned_in_count'];
        $data['assigned_count'] = $workSummary['assigned_count'];
        $data['graded_count'] = $workSummary['graded_count'];

        if ($includeQuestions) {
            $assignment->loadMissing(['sections.questions', 'questions']);
            $data['sections'] = $assignment->sections
                ->map(fn (AssignmentSection $section) => self::section($section, $includeAnswerKey))
                ->values()
                ->all();
            $data['questions'] = $assignment->questions
                ->map(fn ($q) => self::question($q, $includeAnswerKey))
                ->values()
                ->all();
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public static function section(AssignmentSection $section, bool $includeAnswerKey = false): array
    {
        $section->loadMissing('questions');

        return [
            'id' => $section->id,
            'label' => $section->label,
            'title' => $section->title,
            'instructions' => $section->instructions,
            'sort_order' => $section->sort_order,
            'questions' => $section->questions
                ->map(fn (AssignmentQuestion $q) => self::question($q, $includeAnswerKey))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function question(AssignmentQuestion $question, bool $includeAnswerKey = false): array
    {
        $data = [
            'id' => $question->id,
            'section_id' => $question->section_id,
            'sort_order' => $question->sort_order,
            'type' => $question->type->value,
            'type_label' => $question->type->label(),
            'prompt' => $question->prompt,
            'points' => $question->points,
            'options' => $question->mcqOptions(),
            'template' => $question->isFillInBlank() ? $question->blankTemplate() : null,
            'hints' => $question->blankHints(),
            'blank_count' => $question->isFillInBlank() ? $question->blankCount() : null,
            'tokens' => $question->wordTokens(),
            'has_media' => $question->isMediaFile() && $question->hasMedia(),
            'media_original_name' => $question->mediaOriginalName(),
            'existing_media_path' => $question->isMediaFile() ? $question->mediaPath() : null,
            'media_url' => $question->mediaUrl(),
        ];

        if ($includeAnswerKey) {
            $data['correct_index'] = $question->correctMcqIndex();

            if ($question->isFillInBlank()) {
                $data['model_answers'] = $question->config['model_answers'] ?? null;
            }

            if ($question->isWordReorder()) {
                $data['model_sentence'] = $question->config['model_sentence'] ?? null;
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $answer
     */
    public static function formatAnswerForDisplay(AssignmentQuestion $question, array $answer): string
    {
        if ($question->type->value === 'mcq') {
            $index = $answer['selected_index'] ?? null;

            if ($index === null) {
                return '';
            }

            return $question->mcqOptions()[(int) $index] ?? '';
        }

        if ($question->isFillInBlank()) {
            $template = $question->blankTemplate();
            $blanks = $answer['blanks'] ?? [];
            $blankIndex = 0;

            return preg_replace_callback('/___/', function () use (&$blankIndex, $blanks): string {
                return $blanks[$blankIndex++] ?? '___';
            }, $template) ?? $template;
        }

        if ($question->isWordReorder()) {
            return implode(' ', $answer['ordered_tokens'] ?? []);
        }

        return (string) ($answer['text'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    public static function submission(AssignmentSubmission $submission, bool $includeAnnotations = true): array
    {
        $submission->loadMissing(['student', 'assignment', 'answers.question', 'grader']);

        $data = [
            'id' => $submission->id,
            'assignment_id' => $submission->assignment_id,
            'student' => [
                'id' => $submission->student->id,
                'name' => $submission->student->name,
                'email' => $submission->student->email,
            ],
            'status' => $submission->status->value,
            'submitted_at' => $submission->submitted_at->toIso8601String(),
            'auto_score' => $submission->auto_score,
            'final_score' => $submission->final_score,
            'score_overridden' => $submission->score_overridden,
            'feedback' => $submission->feedback,
            'graded_at' => $submission->graded_at?->toIso8601String(),
            'graded_by' => $submission->grader !== null ? [
                'id' => $submission->grader->id,
                'name' => $submission->grader->name,
            ] : null,
            'answers' => $submission->answers->map(fn ($a) => [
                'question_id' => $a->question_id,
                'question' => self::question($a->question, true),
                'answer' => $a->answer,
                'is_correct' => $a->is_correct,
            ])->values()->all(),
        ];

        if ($includeAnnotations) {
            $submission->loadMissing('annotations');
            $data['annotations'] = $submission->annotations->map(fn ($a) => [
                'id' => $a->id,
                'type' => $a->type->value,
                'question_id' => $a->question_id,
                'payload' => $a->payload,
            ])->values()->all();
        }

        return $data;
    }

    /**
     * @return array{roster: list<array<string, mixed>>, summary: array<string, int>}
     */
    public static function studentWork(Assignment $assignment): array
    {
        return [
            'roster' => self::studentWorkRoster($assignment),
            'summary' => self::studentWorkSummary($assignment),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function studentWorkRoster(Assignment $assignment): array
    {
        $assignmentService = app(AssignmentService::class);
        $studentIds = $assignmentService->targetStudentIds($assignment);

        if ($studentIds === []) {
            return [];
        }

        $students = User::query()
            ->whereIn('id', $studentIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $submissions = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        return $students->map(function (User $student) use ($submissions, $assignment): array {
            $submission = $submissions->get($student->id);

            if ($submission === null) {
                return [
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->name,
                        'email' => $student->email,
                    ],
                    'status' => 'assigned',
                    'status_label' => 'Assigned',
                    'is_late' => false,
                    'submission_id' => null,
                    'submitted_at' => null,
                    'final_score' => null,
                    'auto_score' => null,
                ];
            }

            $isGraded = $submission->status === AssignmentSubmissionStatus::Graded;
            $isLate = $assignment->due_at !== null
                && $submission->submitted_at->isAfter($assignment->due_at);

            return [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                ],
                'status' => $isGraded ? 'graded' : 'submitted',
                'status_label' => $isGraded ? 'Graded' : 'Turned in',
                'is_late' => $isLate,
                'submission_id' => $submission->id,
                'submitted_at' => $submission->submitted_at->toIso8601String(),
                'final_score' => $submission->final_score,
                'auto_score' => $submission->auto_score,
            ];
        })->values()->all();
    }

    /**
     * @return array{turned_in_count: int, assigned_count: int, graded_count: int, not_submitted_count: int}
     */
    public static function studentWorkSummary(Assignment $assignment): array
    {
        $roster = self::studentWorkRoster($assignment);
        $assignedCount = count($roster);
        $turnedInCount = collect($roster)->whereIn('status', ['submitted', 'graded'])->count();
        $gradedCount = collect($roster)->where('status', 'graded')->count();

        return [
            'turned_in_count' => $turnedInCount,
            'assigned_count' => $assignedCount,
            'graded_count' => $gradedCount,
            'not_submitted_count' => $assignedCount - $turnedInCount,
        ];
    }

    /**
     * @return list<array{id: int, name: string, language: string, level: string}>
     */
    public static function batchesFor(User $user): array
    {
        if ($user->isTrainingManager()) {
            return Batch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'language', 'level'])
                ->map(fn (Batch $batch) => [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'language' => $batch->language->value,
                    'level' => $batch->level->value,
                ])
                ->all();
        }

        if ($user->isTeacher()) {
            return $user->teachingBatches()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'language', 'level'])
                ->map(fn (Batch $batch) => [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'language' => $batch->language->value,
                    'level' => $batch->level->value,
                ])
                ->all();
        }

        return [];
    }
}
