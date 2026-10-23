<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AssignmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assignments\DuplicateAssignmentRequest;
use App\Http\Requests\Assignments\GradeAssignmentSubmissionRequest;
use App\Http\Requests\Assignments\StoreAssignmentRequest;
use App\Http\Requests\Assignments\UpdateAssignmentRequest;
use App\Models\Assignment;
use App\Models\AssignmentQuestion;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\User;
use App\Services\AssignmentAudioService;
use App\Services\AssignmentQuestionMediaService;
use App\Services\AssignmentService;
use App\Services\AssignmentSubmissionService;
use App\Support\AssignmentPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly AssignmentService $assignments,
        private readonly AssignmentSubmissionService $submissions,
        private readonly AssignmentAudioService $audio,
        private readonly AssignmentQuestionMediaService $questionMedia,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTeacher(), 403);

        $batchIds = $user->teachingBatches()->pluck('id');

        $assignments = Assignment::query()
            ->whereIn('batch_id', $batchIds)
            ->with(['batch', 'creator', 'submissions'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Assignment $assignment) => AssignmentPresenter::assignment($assignment, false));

        return Inertia::render('app/teacher/assignments/index', [
            'assignments' => $assignments,
            'status' => $this->assignmentListStatus($request),
        ]);
    }

    private function assignmentListStatus(Request $request): string
    {
        $status = $request->string('status')->toString();

        return in_array($status, ['published', 'draft'], true)
            ? $status
            : 'published';
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTeacher(), 403);

        return Inertia::render('app/teacher/assignments/create', [
            'batches' => AssignmentPresenter::batchesFor($user),
            'batchStudents' => $this->batchStudentsMap($user),
        ]);
    }

    public function store(StoreAssignmentRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTeacher(), 403);

        $batch = Batch::query()->findOrFail($request->integer('batch_id'));
        abort_unless($this->assignments->actorCanManageBatch($user, $batch), 403);

        $assignment = $this->assignments->create(
            $user,
            $batch,
            $request->assignmentPayload(),
            $request->file('audio'),
        );

        $published = $assignment->status === AssignmentStatus::Published;

        return redirect()
            ->route('teacher.assignments.show', $assignment)
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
        abort_unless($user !== null && $user->isTeacher(), 403);
        abort_unless($this->assignments->actorCanViewAssignment($user, $assignment), 403);

        return Inertia::render('app/teacher/assignments/duplicate', [
            'source' => AssignmentPresenter::assignment($assignment, true, true),
            'batches' => AssignmentPresenter::batchesFor($user),
            'batchStudents' => $this->batchStudentsMap($user),
        ]);
    }

    public function duplicate(DuplicateAssignmentRequest $request, Assignment $assignment): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isTeacher(), 403);

        $batch = Batch::query()->findOrFail($request->integer('batch_id'));
        abort_unless($this->assignments->actorCanManageBatch($user, $batch), 403);

        $duplicate = $this->assignments->duplicate(
            $user,
            $assignment,
            $batch,
            $request->assignmentPayload(),
            $request->file('audio'),
        );

        $published = $duplicate->status === AssignmentStatus::Published;

        return redirect()
            ->route('teacher.assignments.show', $duplicate)
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
        abort_unless($user !== null && $this->assignments->actorCanViewAssignment($user, $assignment), 403);

        $canEdit = $user->isTeacher()
            && $assignment->created_by === $user->id
            && in_array($assignment->status->value, ['draft', 'rejected', 'pending_approval', 'published'], true);

        return Inertia::render('app/teacher/assignments/show', [
            'assignment' => AssignmentPresenter::assignment($assignment),
            'canEdit' => $canEdit,
            'canDuplicate' => true,
            'canGrade' => $assignment->created_by === $user->id,
            ...AssignmentPresenter::studentWork($assignment),
        ]);
    }

    public function edit(Request $request, Assignment $assignment): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $this->assignments->actorCanViewAssignment($user, $assignment), 403);
        abort_unless(
            $user->isTeacher() && $assignment->created_by === $user->id,
            403,
        );

        return Inertia::render('app/teacher/assignments/edit', [
            'assignment' => AssignmentPresenter::assignment($assignment, true, true),
            'batches' => AssignmentPresenter::batchesFor($user),
            'batchStudents' => $this->batchStudentsMap($user),
        ]);
    }

    public function update(UpdateAssignmentRequest $request, Assignment $assignment): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

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
            ->route('teacher.assignments.show', $assignment)
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
        abort_unless($user !== null && $this->assignments->actorCanViewAssignment($user, $assignment), 403);

        $studentWork = AssignmentPresenter::studentWork($assignment);

        return Inertia::render('app/teacher/assignments/submissions/index', [
            'assignment' => AssignmentPresenter::assignment($assignment, false),
            'roster' => $studentWork['roster'],
            'summary' => $studentWork['summary'],
            'canGrade' => $assignment->created_by === $user->id,
        ]);
    }

    public function showSubmission(Request $request, Assignment $assignment, AssignmentSubmission $submission): Response
    {
        abort_unless($submission->assignment_id === $assignment->id, 404);

        $user = $request->user();
        abort_unless($user !== null && $this->submissions->actorCanViewSubmission($user, $submission), 403);

        return Inertia::render('app/teacher/assignments/submissions/show', [
            'assignment' => AssignmentPresenter::assignment($assignment),
            'submission' => AssignmentPresenter::submission($submission),
            'readOnly' => true,
        ]);
    }

    public function grade(Request $request, Assignment $assignment, AssignmentSubmission $submission): Response
    {
        abort_unless($submission->assignment_id === $assignment->id, 404);

        $user = $request->user();
        abort_unless($user !== null && $user->isTeacher(), 403);
        abort_unless($this->submissions->actorCanGradeSubmission($user, $submission), 403);

        return Inertia::render('app/teacher/assignments/submissions/grade', [
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
        abort_unless($user !== null && $user->isTeacher(), 403);
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
            ->route('teacher.assignments.submissions', $assignment)
            ->with('success', 'Submission graded.');
    }

    public function audio(Request $request, Assignment $assignment): mixed
    {
        $user = $request->user();
        abort_unless($user !== null && $this->assignments->actorCanViewAssignment($user, $assignment), 403);

        return $this->audio->stream($assignment);
    }

    public function media(Request $request, Assignment $assignment, AssignmentQuestion $question): mixed
    {
        abort_unless($question->assignment_id === $assignment->id, 404);

        $user = $request->user();
        abort_unless($user !== null && $this->assignments->actorCanViewAssignment($user, $assignment), 403);

        return $this->questionMedia->stream($question);
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
