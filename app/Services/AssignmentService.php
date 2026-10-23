<?php

namespace App\Services;

use App\Enums\AssignmentQuestionType;
use App\Enums\AssignmentStatus;
use App\Enums\AssignmentTargetScope;
use App\Enums\UserRole;
use App\Models\Assignment;
use App\Models\AssignmentQuestion;
use App\Models\AssignmentSection;
use App\Models\AssignmentTarget;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\User;
use App\Notifications\AssignmentPublished;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignmentService
{
    public function __construct(
        private readonly AssignmentAudioService $audio,
        private readonly AssignmentQuestionMediaService $questionMedia,
    ) {}

    public function actorCanManageBatch(User $actor, Batch $batch): bool
    {
        if ($actor->isTrainingManager()) {
            return true;
        }

        if ($actor->isTeacher()) {
            return $batch->teacher_id === $actor->id;
        }

        return false;
    }

    public function actorCanViewAssignment(User $actor, Assignment $assignment): bool
    {
        if ($actor->isTrainingManager()) {
            $assignment->loadMissing('creator');

            return $assignment->created_by === $actor->id
                || $assignment->creator?->isTeacher() === true;
        }

        if ($actor->isTeacher()) {
            return $assignment->batch->teacher_id === $actor->id;
        }

        if ($actor->isStudent()) {
            return $this->isVisibleToStudent($assignment, $actor);
        }

        return false;
    }

    /**
     * @param  Builder<Assignment>  $query
     * @return Builder<Assignment>
     */
    public function scopeVisibleTo(Builder $query, User $actor): Builder
    {
        if ($actor->isTrainingManager()) {
            return $query->where(function (Builder $builder) use ($actor): void {
                $builder
                    ->where('created_by', $actor->id)
                    ->orWhereHas('creator', fn (Builder $creator) => $creator->where('role', UserRole::Teacher));
            });
        }

        if ($actor->isTeacher()) {
            $batchIds = $actor->teachingBatches()->pluck('id');

            return $query->whereIn('batch_id', $batchIds);
        }

        return $query->whereRaw('1 = 0');
    }

    public function actorCanPublish(User $actor): bool
    {
        return $actor->isTrainingManager() || $actor->isTeacher();
    }

    public function isVisibleToStudent(Assignment $assignment, User $student): bool
    {
        if (! $student->isStudent() || ! $assignment->isPublished()) {
            return false;
        }

        $enrollment = $student->batchEnrollment;

        if ($enrollment === null || $enrollment->batch_id !== $assignment->batch_id) {
            return false;
        }

        $targets = $assignment->targets;

        if ($targets->isEmpty()) {
            return false;
        }

        if ($targets->contains(fn (AssignmentTarget $target): bool => $target->scope === AssignmentTargetScope::Batch)) {
            return true;
        }

        return $targets->contains(
            fn (AssignmentTarget $target): bool => $target->scope === AssignmentTargetScope::Student
                && $target->student_id === $student->id,
        );
    }

    /**
     * @return Collection<int, Assignment>
     */
    public function visibleAssignmentsForStudent(User $student): Collection
    {
        $enrollment = $student->batchEnrollment;

        if ($enrollment === null) {
            return collect();
        }

        return Assignment::query()
            ->where('batch_id', $enrollment->batch_id)
            ->where('status', AssignmentStatus::Published)
            ->with(['targets', 'submissions' => fn ($q) => $q->where('student_id', $student->id)])
            ->orderByDesc('published_at')
            ->get()
            ->filter(fn (Assignment $assignment): bool => $this->isVisibleToStudent($assignment, $student))
            ->values();
    }

    /**
     * @param  array{
     *     title: string,
     *     instructions?: string|null,
     *     due_at?: string|null,
     *     target_type: string,
     *     student_ids?: list<int>,
     *     questions: list<array{
     *         type: string,
     *         prompt: string,
     *         points?: int,
     *         options?: list<string>,
     *         correct_index?: int
     *     }>
     * }  $data
     */
    public function create(User $actor, Batch $batch, array $data, ?UploadedFile $audio = null): Assignment
    {
        $this->assertCanManageBatch($actor, $batch);

        return DB::transaction(function () use ($actor, $batch, $data, $audio): Assignment {
            $publish = $this->actorCanPublish($actor) && ($data['publish'] ?? true);
            $status = $publish
                ? AssignmentStatus::Published
                : AssignmentStatus::Draft;

            $assignment = Assignment::query()->create([
                'batch_id' => $batch->id,
                'created_by' => $actor->id,
                'title' => $data['title'],
                'instructions' => $data['instructions'] ?? null,
                'due_at' => $data['due_at'] ?? null,
                'max_score' => 0,
                'status' => $status,
                'published_at' => $publish ? now() : null,
                'approved_by' => $publish ? $actor->id : null,
                'approved_at' => $publish ? now() : null,
            ]);

            $this->syncContent($assignment, $data);
            $this->syncTargets($assignment, $data['target_type'], $data['student_ids'] ?? []);

            if ($audio !== null) {
                $this->audio->store($assignment, $audio);
            }

            if ($publish) {
                $this->notifyPublishedStudents($assignment->fresh(['targets', 'batch']));
            }

            return $assignment->fresh(['sections.questions', 'questions', 'targets', 'batch']);
        });
    }

    /**
     * @param  array{
     *     title: string,
     *     instructions?: string|null,
     *     due_at?: string|null,
     *     target_type: string,
     *     student_ids?: list<int>,
     *     questions: list<array{
     *         type: string,
     *         prompt: string,
     *         points?: int,
     *         options?: list<string>,
     *         correct_index?: int
     *     }>
     * }  $data
     */
    public function update(User $actor, Assignment $assignment, array $data, ?UploadedFile $audio = null, bool $removeAudio = false): Assignment
    {
        $this->assertCanEdit($actor, $assignment);

        return DB::transaction(function () use ($actor, $assignment, $data, $audio, $removeAudio): Assignment {
            $assignment->update([
                'title' => $data['title'],
                'instructions' => $data['instructions'] ?? null,
                'due_at' => $data['due_at'] ?? null,
            ]);

            $this->syncContent($assignment, $data);
            $this->syncTargets($assignment, $data['target_type'], $data['student_ids'] ?? []);

            if ($removeAudio) {
                $this->audio->delete($assignment);
            } elseif ($audio !== null) {
                $this->audio->store($assignment, $audio);
            }

            $shouldPublish = $this->actorCanPublish($actor)
                && ($data['publish'] ?? false)
                && in_array($assignment->status, [
                    AssignmentStatus::Draft,
                    AssignmentStatus::PendingApproval,
                    AssignmentStatus::Rejected,
                ], true);

            if ($shouldPublish) {
                $assignment->update([
                    'status' => AssignmentStatus::Published,
                    'published_at' => now(),
                    'approved_by' => $actor->id,
                    'approved_at' => now(),
                    'rejection_reason' => null,
                ]);

                $this->notifyPublishedStudents($assignment->fresh(['targets', 'batch']));
            }

            return $assignment->fresh(['sections.questions', 'questions', 'targets', 'batch']);
        });
    }

    /**
     * @param  array{
     *     title: string,
     *     instructions?: string|null,
     *     due_at?: string|null,
     *     target_type: string,
     *     student_ids?: list<int>,
     *     publish?: bool,
     *     sections?: list<array<string, mixed>>,
     *     questions?: list<array<string, mixed>>
     * }  $data
     */
    public function duplicate(User $actor, Assignment $source, Batch $targetBatch, array $data, ?UploadedFile $audio = null): Assignment
    {
        if (! $this->actorCanViewAssignment($actor, $source)) {
            throw ValidationException::withMessages([
                'assignment' => 'You cannot duplicate this assignment.',
            ]);
        }

        $this->assertCanManageBatch($actor, $targetBatch);

        $source->loadMissing(['sections.questions']);

        return DB::transaction(function () use ($actor, $source, $targetBatch, $data, $audio): Assignment {
            $publish = $this->actorCanPublish($actor) && ($data['publish'] ?? true);
            $status = $publish
                ? AssignmentStatus::Published
                : AssignmentStatus::Draft;

            $assignment = Assignment::query()->create([
                'batch_id' => $targetBatch->id,
                'created_by' => $actor->id,
                'title' => $data['title'],
                'instructions' => $data['instructions'] ?? null,
                'due_at' => $data['due_at'] ?? null,
                'max_score' => 0,
                'status' => $status,
                'published_at' => $publish ? now() : null,
                'approved_by' => $publish ? $actor->id : null,
                'approved_at' => $publish ? now() : null,
            ]);

            $this->syncContent($assignment, $data);
            $this->syncTargets($assignment, $data['target_type'], $data['student_ids'] ?? []);

            if ($audio !== null) {
                $this->audio->store($assignment, $audio);
            } elseif ($source->hasAudio()) {
                $this->audio->copyFrom($source, $assignment);
            }

            if ($publish) {
                $this->notifyPublishedStudents($assignment->fresh(['targets', 'batch']));
            }

            return $assignment->fresh(['sections.questions', 'questions', 'targets', 'batch']);
        });
    }

    /**
     * @return list<array{
     *     label: string,
     *     title?: string|null,
     *     instructions?: string|null,
     *     questions: list<array<string, mixed>>
     * }>
     */
    public function sectionsPayloadFromAssignment(Assignment $source): array
    {
        $source->loadMissing(['sections.questions', 'questions']);

        $sections = $source->sections;

        if ($sections->isEmpty()) {
            return [
                [
                    'label' => 'General',
                    'title' => null,
                    'instructions' => null,
                    'questions' => $source->questions
                        ->map(fn (AssignmentQuestion $question): array => $this->questionPayloadFromModel($question))
                        ->values()
                        ->all(),
                ],
            ];
        }

        return $sections
            ->map(fn (AssignmentSection $section): array => [
                'label' => $section->label,
                'title' => $section->title,
                'instructions' => $section->instructions,
                'questions' => $section->questions
                    ->map(fn (AssignmentQuestion $question): array => $this->questionPayloadFromModel($question))
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function questionPayloadFromModel(AssignmentQuestion $question): array
    {
        $payload = [
            'type' => $question->type->value,
            'prompt' => $question->prompt,
            'points' => $question->points,
        ];

        if ($question->isMcq()) {
            $payload['options'] = $question->mcqOptions();
            $payload['correct_index'] = $question->correctMcqIndex() ?? 0;
        }

        if ($question->isFillInBlank()) {
            $payload['template'] = $question->blankTemplate();
            $payload['hints'] = $question->blankHints();
            $payload['model_answers'] = $question->config['model_answers'] ?? null;
        }

        if ($question->isWordReorder()) {
            $payload['tokens'] = $question->wordTokens();
            $payload['model_sentence'] = $question->config['model_sentence'] ?? null;
        }

        if ($question->isLink()) {
            $payload['url'] = $question->mediaUrl();
        }

        if ($question->isMediaFile() && $question->hasMedia()) {
            $payload['existing_media_path'] = $question->mediaPath();
            $payload['media_original_name'] = $question->mediaOriginalName();
        }

        return $payload;
    }

    /**
     * @param  array{sections?: list<array<string, mixed>>, questions?: list<array<string, mixed>>}  $data
     */
    public function syncContent(Assignment $assignment, array $data): void
    {
        if (isset($data['sections']) && is_array($data['sections'])) {
            $this->syncSections($assignment, $data['sections']);

            return;
        }

        $questions = $data['questions'] ?? [];

        $this->syncSections($assignment, [
            [
                'label' => 'General',
                'title' => null,
                'instructions' => null,
                'questions' => $questions,
            ],
        ]);
    }

    /**
     * @param  list<array{
     *     label: string,
     *     title?: string|null,
     *     instructions?: string|null,
     *     questions: list<array<string, mixed>>
     * }>  $sections
     */
    public function syncSections(Assignment $assignment, array $sections): void
    {
        $assignment->questions()->delete();
        $assignment->sections()->delete();

        $maxScore = 0;
        $usedMediaPaths = [];

        foreach (array_values($sections) as $sectionIndex => $sectionData) {
            $section = AssignmentSection::query()->create([
                'assignment_id' => $assignment->id,
                'label' => $sectionData['label'],
                'title' => $sectionData['title'] ?? null,
                'instructions' => $sectionData['instructions'] ?? null,
                'sort_order' => $sectionIndex,
            ]);

            foreach (array_values($sectionData['questions'] ?? []) as $questionIndex => $questionData) {
                $type = AssignmentQuestionType::from($questionData['type']);
                $points = (int) ($questionData['points'] ?? 1);
                $maxScore += $points;

                $config = $this->buildQuestionConfig($assignment, $type, $questionData);

                if ($type->isMediaFile() && is_array($config) && isset($config['path']) && is_string($config['path'])) {
                    $usedMediaPaths[] = $config['path'];
                }

                AssignmentQuestion::query()->create([
                    'assignment_id' => $assignment->id,
                    'section_id' => $section->id,
                    'sort_order' => $questionIndex,
                    'type' => $type,
                    'prompt' => $questionData['prompt'],
                    'points' => $points,
                    'config' => $config,
                ]);
            }
        }

        $assignment->update(['max_score' => $maxScore]);
        $this->questionMedia->pruneOrphans($assignment, $usedMediaPaths);
    }

    /**
     * @param  array<string, mixed>  $questionData
     * @return array<string, mixed>|null
     */
    private function buildQuestionConfig(Assignment $assignment, AssignmentQuestionType $type, array $questionData): ?array
    {
        if ($type === AssignmentQuestionType::Mcq) {
            return [
                'options' => array_values($questionData['options'] ?? []),
                'correct_index' => (int) ($questionData['correct_index'] ?? 0),
            ];
        }

        if ($type === AssignmentQuestionType::FillInBlank) {
            $template = (string) ($questionData['template'] ?? $questionData['prompt']);
            $template = (string) preg_replace('/_{3,}/', '___', $template);
            $blankCount = substr_count($template, '___');

            return [
                'template' => $template,
                'hints' => array_values($questionData['hints'] ?? []),
                'blank_count' => $blankCount > 0 ? $blankCount : 1,
                'model_answers' => $questionData['model_answers'] ?? null,
            ];
        }

        if ($type === AssignmentQuestionType::WordReorder) {
            return [
                'tokens' => array_values($questionData['tokens'] ?? []),
                'model_sentence' => $questionData['model_sentence'] ?? null,
            ];
        }

        if ($type->isMediaItem()) {
            return $this->questionMedia->resolveConfig($assignment, $type, $questionData);
        }

        return null;
    }

    /**
     * @param  list<array{
     *     type: string,
     *     prompt: string,
     *     points?: int,
     *     options?: list<string>,
     *     correct_index?: int
     * }>  $questions
     *
     * @deprecated Use syncSections instead
     */
    public function syncQuestions(Assignment $assignment, array $questions): void
    {
        $this->syncSections($assignment, [
            [
                'label' => 'General',
                'title' => null,
                'instructions' => null,
                'questions' => $questions,
            ],
        ]);
    }

    /**
     * @param  list<int>  $studentIds
     */
    public function syncTargets(Assignment $assignment, string $targetType, array $studentIds): void
    {
        $assignment->targets()->delete();

        if ($targetType === AssignmentTargetScope::Batch->value) {
            AssignmentTarget::query()->create([
                'assignment_id' => $assignment->id,
                'scope' => AssignmentTargetScope::Batch,
                'student_id' => null,
            ]);

            return;
        }

        $uniqueIds = array_values(array_unique(array_map('intval', $studentIds)));

        foreach ($uniqueIds as $studentId) {
            AssignmentTarget::query()->create([
                'assignment_id' => $assignment->id,
                'scope' => AssignmentTargetScope::Student,
                'student_id' => $studentId,
            ]);
        }
    }

    /**
     * @return list<array{id: int, name: string, email: string}>
     */
    public function enrollableStudentsForBatch(Batch $batch): array
    {
        return BatchEnrollment::query()
            ->where('batch_id', $batch->id)
            ->with('student:id,name,email')
            ->get()
            ->map(fn (BatchEnrollment $enrollment): array => [
                'id' => $enrollment->student_id,
                'name' => $enrollment->student?->name ?? '',
                'email' => $enrollment->student?->email ?? '',
            ])
            ->all();
    }

    public function notifyPublishedStudents(Assignment $assignment): void
    {
        $studentIds = $this->targetStudentIds($assignment);

        User::query()
            ->whereIn('id', $studentIds)
            ->each(fn (User $student) => $student->notify(
                (new AssignmentPublished($assignment))->afterCommit(),
            ));
    }

    /**
     * @return list<int>
     */
    public function targetStudentIds(Assignment $assignment): array
    {
        $assignment->loadMissing('targets');

        if ($assignment->targets->contains(fn (AssignmentTarget $t): bool => $t->scope === AssignmentTargetScope::Batch)) {
            return BatchEnrollment::query()
                ->where('batch_id', $assignment->batch_id)
                ->pluck('student_id')
                ->all();
        }

        return $assignment->targets
            ->where('scope', AssignmentTargetScope::Student)
            ->pluck('student_id')
            ->filter()
            ->values()
            ->all();
    }

    private function assertCanManageBatch(User $actor, Batch $batch): void
    {
        if (! $this->actorCanManageBatch($actor, $batch)) {
            throw ValidationException::withMessages([
                'batch' => 'You cannot manage assignments for this batch.',
            ]);
        }
    }

    private function assertCanEdit(User $actor, Assignment $assignment): void
    {
        $editableStatuses = [
            AssignmentStatus::Draft,
            AssignmentStatus::PendingApproval,
            AssignmentStatus::Rejected,
            AssignmentStatus::Published,
        ];

        if ($actor->isTrainingManager()
            && $this->actorCanViewAssignment($actor, $assignment)
            && in_array($assignment->status, $editableStatuses, true)
        ) {
            return;
        }

        if ($actor->isTeacher()
            && $assignment->created_by === $actor->id
            && in_array($assignment->status, $editableStatuses, true)
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'assignment' => 'You cannot edit this assignment.',
        ]);
    }
}
