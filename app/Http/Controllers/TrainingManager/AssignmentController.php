<?php

namespace App\Http\Controllers\TrainingManager;

use App\Enums\AssignmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assignments\DuplicateAssignmentRequest;
use App\Http\Requests\Assignments\GradeAssignmentSubmissionRequest;
use App\Http\Requests\Assignments\MoveAssignmentToFolderRequest;
use App\Http\Requests\Assignments\StoreAssignmentFolderRequest;
use App\Http\Requests\Assignments\StoreAssignmentRequest;
use App\Http\Requests\Assignments\UpdateAssignmentFolderRequest;
use App\Http\Requests\Assignments\UpdateAssignmentRequest;
use App\Models\Assignment;
use App\Models\AssignmentFolder;
use App\Models\AssignmentQuestion;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\User;
use App\Services\AssignmentAudioService;
use App\Services\AssignmentFolderService;
use App\Services\AssignmentQuestionMediaService;
use App\Services\AssignmentService;
use App\Services\AssignmentSubmissionService;
use App\Support\AssignmentPresenter;
use App\Support\TrainerPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly AssignmentService $assignments,
        private readonly AssignmentSubmissionService $submissions,
        private readonly AssignmentAudioService $audio,
        private readonly AssignmentFolderService $folders,
        private readonly AssignmentQuestionMediaService $questionMedia,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTrainingManager(), 403);

        $creatorId = $request->integer('creator') ?: null;
        $currentCreator = null;

        if ($creatorId !== null) {
            $currentCreator = User::query()->find($creatorId);
            abort_unless($currentCreator !== null, 404);
            abort_unless(
                $currentCreator->id === $user->id || $currentCreator->isTeacher(),
                404,
            );
        }

        if ($currentCreator === null) {
            return Inertia::render('app/training-manager/assignments/index', [
                'creators' => $this->creatorsForIndex($user),
                'assignments' => [],
                'folders' => [],
                'currentFolder' => null,
                'currentCreator' => null,
                'status' => 'published',
                'tabCounts' => ['published' => 0, 'draft' => 0],
            ]);
        }

        $status = $this->assignmentListStatus($request);
        $statuses = $this->folders->statusesForTab($status);
        $folderId = $request->integer('folder') ?: null;
        $currentFolder = $folderId !== null
            ? AssignmentFolder::query()->find($folderId)
            : null;

        if ($folderId !== null && $currentFolder === null) {
            abort(404);
        }

        $assignments = $this->assignments
            ->scopeVisibleTo(Assignment::query(), $user)
            ->with(['batch', 'creator', 'submissions'])
            ->where('created_by', $currentCreator->id)
            ->whereIn('status', $statuses)
            ->when(
                $folderId === null,
                fn ($query) => $query->whereNull('folder_id'),
                fn ($query) => $query->where('folder_id', $folderId),
            )
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Assignment $assignment) => AssignmentPresenter::assignment($assignment, false));

        return Inertia::render('app/training-manager/assignments/index', [
            'creators' => [],
            'assignments' => $assignments,
            'folders' => $this->folders->foldersForIndex(
                $statuses,
                $user,
                $currentCreator->id,
            )->values()->all(),
            'currentFolder' => $currentFolder !== null
                ? ['id' => $currentFolder->id, 'name' => $currentFolder->name]
                : null,
            'currentCreator' => [
                'id' => $currentCreator->id,
                'name' => $currentCreator->id === $user->id
                    ? 'My Assignments'
                    : $currentCreator->name,
                'role' => $currentCreator->role->value,
                'role_label' => $currentCreator->id === $user->id
                    ? 'You'
                    : ($currentCreator->isTrainingManager() ? 'Training Manager' : 'Faculty'),
                'is_mine' => $currentCreator->id === $user->id,
            ],
            'status' => $status,
            'tabCounts' => $this->tabCounts($user, $currentCreator->id),
        ]);
    }

    /**
     * @return list<array{
     *     id: int,
     *     name: string,
     *     role: string,
     *     role_label: string,
     *     photo_url: string|null,
     *     published_count: int,
     *     draft_count: int,
     *     assignments_count: int,
     *     is_mine: bool
     * }>
     */
    private function creatorsForIndex(User $actor): array
    {
        /** @var Collection<int, Assignment> $assignments */
        $assignments = $this->assignments
            ->scopeVisibleTo(Assignment::query(), $actor)
            ->with('creator')
            ->get();

        $creators = $assignments
            ->groupBy('created_by')
            ->map(function (Collection $group) use ($actor): ?array {
                /** @var User|null $creator */
                $creator = $group->first()?->creator;

                if ($creator === null) {
                    return null;
                }

                return $this->creatorCardPayload($creator, $group, $actor);
            })
            ->filter()
            ->keyBy('id');

        if (! $creators->has($actor->id)) {
            $creators->put(
                $actor->id,
                $this->creatorCardPayload($actor, collect(), $actor),
            );
        }

        return $creators
            ->sortBy([
                fn (array $creator): int => $creator['is_mine'] ? 0 : 1,
                fn (array $creator): string => mb_strtolower($creator['name']),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Assignment>  $group
     * @return array{
     *     id: int,
     *     name: string,
     *     role: string,
     *     role_label: string,
     *     photo_url: string|null,
     *     published_count: int,
     *     draft_count: int,
     *     assignments_count: int,
     *     is_mine: bool
     * }
     */
    private function creatorCardPayload(User $creator, Collection $group, User $actor): array
    {
        $isMine = $creator->id === $actor->id;
        $publishedCount = $group
            ->where('status', AssignmentStatus::Published)
            ->count();
        $draftCount = $group
            ->filter(fn (Assignment $assignment): bool => in_array(
                $assignment->status,
                [AssignmentStatus::Draft, AssignmentStatus::Rejected, AssignmentStatus::PendingApproval],
                true,
            ))
            ->count();

        return [
            'id' => $creator->id,
            'name' => $isMine ? 'My Assignments' : $creator->name,
            'role' => $creator->role->value,
            'role_label' => $isMine
                ? 'You'
                : ($creator->role === UserRole::TrainingManager
                    ? 'Training Manager'
                    : 'Faculty'),
            'photo_url' => TrainerPresenter::photoUrl($creator->avatar_path),
            'published_count' => $publishedCount,
            'draft_count' => $draftCount,
            'assignments_count' => $group->count(),
            'is_mine' => $isMine,
        ];
    }

    /**
     * @return array{published: int, draft: int}
     */
    private function tabCounts(User $actor, int $creatorId): array
    {
        $published = $this->assignments
            ->scopeVisibleTo(Assignment::query(), $actor)
            ->where('created_by', $creatorId)
            ->where('status', AssignmentStatus::Published)
            ->count();

        $draft = $this->assignments
            ->scopeVisibleTo(Assignment::query(), $actor)
            ->where('created_by', $creatorId)
            ->whereIn('status', [
                AssignmentStatus::Draft,
                AssignmentStatus::Rejected,
                AssignmentStatus::PendingApproval,
            ])
            ->count();

        return [
            'published' => $published,
            'draft' => $draft,
        ];
    }

    public function storeFolder(StoreAssignmentFolderRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTrainingManager(), 403);

        $this->folders->create($user, $request->string('name')->toString());

        return redirect()
            ->back()
            ->with('success', 'Folder created.');
    }

    public function updateFolder(UpdateAssignmentFolderRequest $request, AssignmentFolder $folder): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTrainingManager(), 403);

        $this->folders->rename($user, $folder, $request->string('name')->toString());

        return redirect()
            ->back()
            ->with('success', 'Folder renamed.');
    }

    public function destroyFolder(Request $request, AssignmentFolder $folder): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTrainingManager(), 403);

        $this->folders->delete($user, $folder);

        $params = [
            'status' => $this->assignmentListStatus($request),
        ];

        $creatorId = $request->integer('creator') ?: null;
        if ($creatorId !== null) {
            $params['creator'] = $creatorId;
        }

        return redirect()
            ->route('training-manager.assignments.index', $params)
            ->with('success', 'Folder deleted.');
    }

    public function moveToFolder(MoveAssignmentToFolderRequest $request, Assignment $assignment): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTrainingManager(), 403);
        abort_unless($this->assignments->actorCanViewAssignment($user, $assignment), 403);

        $folderId = $request->input('folder_id');
        $folderId = $folderId !== null ? (int) $folderId : null;

        $this->folders->moveAssignment($user, $assignment, $folderId);

        return redirect()
            ->back()
            ->with('success', 'Assignment moved.');
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTrainingManager(), 403);

        return Inertia::render('app/training-manager/assignments/create', [
            'batches' => AssignmentPresenter::batchesFor($user),
            'batchStudents' => $this->batchStudentsMap($user),
        ]);
    }

    public function store(StoreAssignmentRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTrainingManager(), 403);

        $batch = Batch::query()->findOrFail($request->integer('batch_id'));

        $assignment = $this->assignments->create(
            $user,
            $batch,
            $request->assignmentPayload(),
            $request->file('audio'),
        );

        $published = $assignment->status === AssignmentStatus::Published;

        return redirect()
            ->route('training-manager.assignments.show', $assignment)
            ->with(
                'success',
                $published
                    ? 'Assignment published.'
                    : 'Draft saved. You can edit and publish later.',
            );
    }

    public function duplicateForm(Request $request, Assignment $assignment): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTrainingManager(), 403);
        abort_unless($this->assignments->actorCanViewAssignment($user, $assignment), 403);

        return Inertia::render('app/training-manager/assignments/duplicate', [
            'source' => AssignmentPresenter::assignment($assignment, true, true),
            'batches' => AssignmentPresenter::batchesFor($user),
            'batchStudents' => $this->batchStudentsMap($user),
        ]);
    }

    public function duplicate(DuplicateAssignmentRequest $request, Assignment $assignment): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTrainingManager(), 403);
        abort_unless($this->assignments->actorCanViewAssignment($user, $assignment), 403);

        $batch = Batch::query()->findOrFail($request->integer('batch_id'));

        $duplicate = $this->assignments->duplicate(
            $user,
            $assignment,
            $batch,
            $request->assignmentPayload(),
            $request->file('audio'),
        );

        $published = $duplicate->status === AssignmentStatus::Published;

        return redirect()
            ->route('training-manager.assignments.show', $duplicate)
            ->with(
                'success',
                $published
                    ? 'Assignment duplicated and published.'
                    : 'Assignment duplicated as draft.',
            );
    }

    public function show(Request $request, Assignment $assignment): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTrainingManager(), 403);
        abort_unless($this->assignments->actorCanViewAssignment($user, $assignment), 403);

        return Inertia::render('app/training-manager/assignments/show', [
            'assignment' => AssignmentPresenter::assignment($assignment),
            'canEdit' => true,
            'canGrade' => true,
            ...AssignmentPresenter::studentWork($assignment),
        ]);
    }

    public function edit(Request $request, Assignment $assignment): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTrainingManager(), 403);
        abort_unless($this->assignments->actorCanViewAssignment($user, $assignment), 403);

        return Inertia::render('app/training-manager/assignments/edit', [
            'assignment' => AssignmentPresenter::assignment($assignment, true, true),
            'batches' => AssignmentPresenter::batchesFor($user),
            'batchStudents' => $this->batchStudentsMap($user),
        ]);
    }

    public function update(UpdateAssignmentRequest $request, Assignment $assignment): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTrainingManager(), 403);
        abort_unless($this->assignments->actorCanViewAssignment($user, $assignment), 403);

        $this->assignments->update(
            $user,
            $assignment,
            $request->assignmentPayload(),
            $request->file('audio'),
            $request->boolean('remove_audio'),
        );

        $assignment->refresh();
        $publishedNow = $assignment->status === AssignmentStatus::Published
            && $request->boolean('publish');

        return redirect()
            ->route('training-manager.assignments.show', $assignment)
            ->with(
                'success',
                $publishedNow
                    ? 'Assignment published.'
                    : 'Assignment updated.',
            );
    }

    public function submissions(Request $request, Assignment $assignment): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTrainingManager(), 403);
        abort_unless($this->assignments->actorCanViewAssignment($user, $assignment), 403);

        $studentWork = AssignmentPresenter::studentWork($assignment);

        return Inertia::render('app/training-manager/assignments/submissions/index', [
            'assignment' => AssignmentPresenter::assignment($assignment, false),
            'roster' => $studentWork['roster'],
            'summary' => $studentWork['summary'],
            'canGrade' => true,
        ]);
    }

    public function grade(Request $request, Assignment $assignment, AssignmentSubmission $submission): Response
    {
        abort_unless($submission->assignment_id === $assignment->id, 404);

        $user = $request->user();
        abort_unless($user !== null && $user->isTrainingManager(), 403);
        abort_unless($this->submissions->actorCanGradeSubmission($user, $submission), 403);

        return Inertia::render('app/training-manager/assignments/submissions/grade', [
            'assignment' => AssignmentPresenter::assignment($assignment),
            'submission' => AssignmentPresenter::submission($submission),
        ]);
    }

    public function saveGrade(
        GradeAssignmentSubmissionRequest $request,
        Assignment $assignment,
        AssignmentSubmission $submission,
    ): RedirectResponse {
        abort_unless($submission->assignment_id === $assignment->id, 404);

        $user = $request->user();
        abort_unless($user !== null && $user->isTrainingManager(), 403);
        abort_unless($this->submissions->actorCanGradeSubmission($user, $submission), 403);

        $validated = $request->validated();

        $this->submissions->grade(
            $user,
            $submission,
            (int) $validated['final_score'],
            $validated['feedback'] ?? null,
            (bool) $validated['score_overridden'],
            $validated['annotations'] ?? [],
        );

        return redirect()
            ->route('training-manager.assignments.submissions', $assignment)
            ->with('success', 'Submission graded.');
    }

    public function audio(Request $request, Assignment $assignment): mixed
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        if ($user->isStudent() && ! $this->assignments->isVisibleToStudent($assignment, $user)) {
            abort(403);
        }

        if (($user->isTeacher() || $user->isTrainingManager())
            && ! $this->assignments->actorCanViewAssignment($user, $assignment)
        ) {
            abort(403);
        }

        if (! $user->isTrainingManager() && ! $user->isTeacher() && ! $user->isStudent()) {
            abort(403);
        }

        return $this->audio->stream($assignment);
    }

    public function media(Request $request, Assignment $assignment, AssignmentQuestion $question): mixed
    {
        abort_unless($question->assignment_id === $assignment->id, 404);

        $user = $request->user();
        abort_unless($user !== null && $user->isTrainingManager(), 403);
        abort_unless($this->assignments->actorCanViewAssignment($user, $assignment), 403);

        return $this->questionMedia->stream($question);
    }

    private function assignmentListStatus(Request $request): string
    {
        $status = $request->string('status')->toString();

        return in_array($status, ['published', 'draft'], true)
            ? $status
            : 'published';
    }

    /**
     * @return array<string, list<array{id: int, name: string, email: string}>>
     */
    private function batchStudentsMap(User $user): array
    {
        $map = [];

        foreach (AssignmentPresenter::batchesFor($user) as $batch) {
            $batchModel = Batch::query()->find($batch['id']);

            if ($batchModel !== null) {
                $map[(string) $batch['id']] = $this->assignments->enrollableStudentsForBatch($batchModel);
            }
        }

        return $map;
    }
}
