<?php

namespace App\Services;

use App\Enums\AssignmentQuestionType;
use App\Enums\AssignmentSubmissionStatus;
use App\Enums\UserRole;
use App\Models\Assignment;
use App\Models\AssignmentAnswer;
use App\Models\AssignmentQuestion;
use App\Models\AssignmentSubmission;
use App\Models\User;
use App\Notifications\AssignmentGraded;
use App\Notifications\AssignmentSubmitted;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignmentSubmissionService
{
    public function __construct(
        private readonly AssignmentService $assignments,
        private readonly SubmissionAnnotationService $annotations,
    ) {}

    public function actorCanViewSubmission(User $actor, AssignmentSubmission $submission): bool
    {
        $submission->loadMissing('assignment.batch');

        if ($actor->isTrainingManager()) {
            return $this->assignments->actorCanViewAssignment($actor, $submission->assignment);
        }

        if ($actor->isTeacher()) {
            return $submission->assignment->batch->teacher_id === $actor->id;
        }

        if ($actor->isStudent()) {
            return $submission->student_id === $actor->id;
        }

        return false;
    }

    public function actorCanGradeSubmission(User $actor, AssignmentSubmission $submission): bool
    {
        $submission->loadMissing('assignment');

        if ($actor->isTrainingManager()) {
            return $this->assignments->actorCanViewAssignment($actor, $submission->assignment);
        }

        if ($actor->isTeacher()) {
            return $submission->assignment->created_by === $actor->id;
        }

        return false;
    }

    /**
     * @param  list<array{question_id: int, selected_index?: int, text?: string}>  $answers
     */
    public function submit(User $student, Assignment $assignment, array $answers): AssignmentSubmission
    {
        if (! $student->isStudent()) {
            throw ValidationException::withMessages([
                'assignment' => 'Only students can submit assignments.',
            ]);
        }

        if (! $this->assignments->isVisibleToStudent($assignment, $student)) {
            throw ValidationException::withMessages([
                'assignment' => 'This assignment is not available to you.',
            ]);
        }

        if ($assignment->submissions()->where('student_id', $student->id)->exists()) {
            throw ValidationException::withMessages([
                'assignment' => 'You have already submitted this assignment.',
            ]);
        }

        $assignment->load('questions');

        return DB::transaction(function () use ($student, $assignment, $answers): AssignmentSubmission {
            $submission = AssignmentSubmission::query()->create([
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
                'status' => AssignmentSubmissionStatus::Submitted,
                'submitted_at' => now(),
            ]);

            $answersByQuestion = collect($answers)->keyBy('question_id');

            foreach ($assignment->questions as $question) {
                $answerData = $answersByQuestion->get($question->id);

                if ($answerData === null) {
                    throw ValidationException::withMessages([
                        "answers.{$question->id}" => 'This question requires an answer.',
                    ]);
                }

                $storedAnswer = $this->normalizeAnswer($question, $answerData);
                $isCorrect = $this->evaluateMcq($question, $storedAnswer);

                AssignmentAnswer::query()->create([
                    'submission_id' => $submission->id,
                    'question_id' => $question->id,
                    'answer' => $storedAnswer,
                    'is_correct' => $isCorrect,
                ]);
            }

            $autoScore = $this->calculateAutoScore($submission->fresh(['answers.question']));
            $submission->update(['auto_score' => $autoScore]);

            $this->notifySubmitted($submission->fresh(['assignment.batch', 'assignment.creator', 'student']));

            return $submission->fresh(['answers.question', 'assignment']);
        });
    }

    public function calculateAutoScore(AssignmentSubmission $submission): int
    {
        $submission->loadMissing('answers.question');

        $score = 0;

        foreach ($submission->answers as $answer) {
            if ($answer->is_correct === true && $answer->question !== null) {
                $score += $answer->question->points;
            }
        }

        return $score;
    }

    /**
     * @param  list<array{type: string, question_id?: int|null, payload: array<string, mixed>}>  $annotations
     */
    public function grade(
        User $actor,
        AssignmentSubmission $submission,
        int $finalScore,
        ?string $feedback,
        bool $overridden,
        array $annotations = [],
    ): AssignmentSubmission {
        if (! $this->actorCanGradeSubmission($actor, $submission)) {
            throw ValidationException::withMessages([
                'submission' => 'You cannot grade this submission.',
            ]);
        }

        if ($submission->status !== AssignmentSubmissionStatus::Submitted) {
            throw ValidationException::withMessages([
                'submission' => 'This submission has already been graded.',
            ]);
        }

        return DB::transaction(function () use ($actor, $submission, $finalScore, $feedback, $overridden, $annotations): AssignmentSubmission {
            $autoScore = $this->calculateAutoScore($submission);

            $submission->update([
                'status' => AssignmentSubmissionStatus::Graded,
                'auto_score' => $autoScore,
                'final_score' => $finalScore,
                'score_overridden' => $overridden,
                'feedback' => $feedback,
                'graded_by' => $actor->id,
                'graded_at' => now(),
            ]);

            $this->annotations->sync($actor, $submission, $annotations);

            $submission->student->notify(
                (new AssignmentGraded($submission->fresh(['assignment'])))->afterCommit(),
            );

            return $submission->fresh(['answers.question', 'annotations', 'assignment', 'student']);
        });
    }

    /**
     * @param  array{question_id: int, selected_index?: int, text?: string, blanks?: list<string>, ordered_tokens?: list<string>}  $answerData
     * @return array<string, mixed>
     */
    private function normalizeAnswer(AssignmentQuestion $question, array $answerData): array
    {
        if ($question->type === AssignmentQuestionType::Mcq) {
            if (! isset($answerData['selected_index'])) {
                throw ValidationException::withMessages([
                    "answers.{$question->id}" => 'Select an option.',
                ]);
            }

            return ['selected_index' => (int) $answerData['selected_index']];
        }

        if ($question->type === AssignmentQuestionType::FillInBlank) {
            $blanks = array_values($answerData['blanks'] ?? []);
            $expected = $question->blankCount();

            if (count($blanks) !== $expected) {
                throw ValidationException::withMessages([
                    "answers.{$question->id}" => 'Fill in all blanks.',
                ]);
            }

            $normalized = array_map(fn (string $blank): string => trim($blank), $blanks);

            foreach ($normalized as $blank) {
                if ($blank === '') {
                    throw ValidationException::withMessages([
                        "answers.{$question->id}" => 'Fill in all blanks.',
                    ]);
                }
            }

            return ['blanks' => $normalized];
        }

        if ($question->type === AssignmentQuestionType::WordReorder) {
            $ordered = array_values($answerData['ordered_tokens'] ?? []);
            $expected = $question->wordTokens();

            if (count($ordered) !== count($expected)) {
                throw ValidationException::withMessages([
                    "answers.{$question->id}" => 'Use all word tiles.',
                ]);
            }

            sort($ordered);
            $expectedSorted = $expected;
            sort($expectedSorted);

            if ($ordered !== $expectedSorted) {
                throw ValidationException::withMessages([
                    "answers.{$question->id}" => 'Invalid word selection.',
                ]);
            }

            return ['ordered_tokens' => array_values($answerData['ordered_tokens'] ?? [])];
        }

        $text = trim((string) ($answerData['text'] ?? ''));

        if ($text === '') {
            throw ValidationException::withMessages([
                "answers.{$question->id}" => 'Enter an answer.',
            ]);
        }

        return ['text' => $text];
    }

    /**
     * @param  array<string, mixed>  $storedAnswer
     */
    private function evaluateMcq(AssignmentQuestion $question, array $storedAnswer): ?bool
    {
        if ($question->type !== AssignmentQuestionType::Mcq) {
            return null;
        }

        $correctIndex = $question->correctMcqIndex();
        $selectedIndex = $storedAnswer['selected_index'] ?? null;

        if ($correctIndex === null || $selectedIndex === null) {
            return false;
        }

        return (int) $selectedIndex === $correctIndex;
    }

    private function notifySubmitted(AssignmentSubmission $submission): void
    {
        $assignment = $submission->assignment;

        User::query()
            ->where('role', UserRole::TrainingManager)
            ->each(fn (User $tm) => $tm->notify(
                (new AssignmentSubmitted($submission))->afterCommit(),
            ));

        $assignment->creator?->notify(
            (new AssignmentSubmitted($submission))->afterCommit(),
        );
    }
}
