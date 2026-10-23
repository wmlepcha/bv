<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assignments\SubmitAssignmentAnswersRequest;
use App\Models\Assignment;
use App\Models\AssignmentQuestion;
use App\Models\AssignmentSubmission;
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
        abort_unless($user !== null && $user->isStudent(), 403);

        $assignments = $this->assignments->visibleAssignmentsForStudent($user)
            ->map(function (Assignment $assignment) {
                $data = AssignmentPresenter::assignment($assignment, false);
                $submission = $assignment->submissions->first();
                $data['submission_status'] = $submission?->status->value;
                $data['submission_id'] = $submission?->id;

                return $data;
            });

        return Inertia::render('app/student/assignments/index', [
            'assignments' => $assignments,
        ]);
    }

    public function show(Request $request, Assignment $assignment): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $this->assignments->isVisibleToStudent($assignment, $user), 403);

        $submission = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $user->id)
            ->first();

        if ($submission !== null && $submission->isGraded()) {
            return redirect()->route('student.assignments.submission', $assignment);
        }

        return Inertia::render('app/student/assignments/show', [
            'assignment' => AssignmentPresenter::assignment($assignment),
            'hasSubmitted' => $submission !== null,
            'submission' => $submission !== null
                ? AssignmentPresenter::submission($submission, false)
                : null,
        ]);
    }

    public function submit(SubmitAssignmentAnswersRequest $request, Assignment $assignment): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isStudent(), 403);

        $this->submissions->submit($user, $assignment, $request->validated('answers'));

        return redirect()
            ->route('student.assignments.show', $assignment)
            ->with('success', 'Assignment submitted.');
    }

    public function submission(Request $request, Assignment $assignment): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $this->assignments->isVisibleToStudent($assignment, $user), 403);

        $submission = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $user->id)
            ->firstOrFail();

        return Inertia::render('app/student/assignments/submission', [
            'assignment' => AssignmentPresenter::assignment($assignment),
            'submission' => AssignmentPresenter::submission($submission),
        ]);
    }

    public function audio(Request $request, Assignment $assignment): mixed
    {
        $user = $request->user();
        abort_unless($user !== null && $this->assignments->isVisibleToStudent($assignment, $user), 403);

        return $this->audio->stream($assignment);
    }

    public function media(Request $request, Assignment $assignment, AssignmentQuestion $question): mixed
    {
        abort_unless($question->assignment_id === $assignment->id, 404);

        $user = $request->user();
        abort_unless($user !== null && $this->assignments->isVisibleToStudent($assignment, $user), 403);

        return $this->questionMedia->stream($question);
    }
}
