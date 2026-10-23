<?php

namespace App\Http\Controllers\Recruiter;

use App\Enums\CourseLevel;
use App\Enums\Profession;
use App\Http\Controllers\Controller;
use App\Http\Requests\Recruiter\CandidateFilterRequest;
use App\Models\BatchEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\BatchTransferService;
use App\Services\CvTranslationService;
use App\Support\CvProfilePresenter;
use App\Support\LanguageProgressPresenter;
use App\Support\RecruiterCandidatePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class StudentProfileController extends Controller
{
    public function index(CandidateFilterRequest $request): Response
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        $query = StudentProfile::query()
            ->listedForRecruiters()
            ->when($viewer->isRecruiter() || $viewer->isEmployee(), fn ($q) => $q->registeredForPlacement())
            ->with('user.batchEnrollment.batch')
            ->matching($request->validated());

        if ($viewer->isTeacher()) {
            $query->whereHas('user.batchEnrollment.batch', function ($q) use ($viewer): void {
                $q->where('teacher_id', $viewer->id);
            });
        }

        $profiles = $query
            ->latest()
            ->paginate(24)
            ->withQueryString()
            ->through(fn (StudentProfile $profile): array => RecruiterCandidatePresenter::card($profile));

        return Inertia::render('app/recruiter/students/index', [
            'profiles' => $profiles,
            'filters' => [
                'profession' => $request->validated('profession'),
                'experience' => $request->validated('experience'),
                'level' => $request->validated('level'),
                'status' => $request->validated('status'),
                'q' => $request->validated('q'),
            ],
            'filterOptions' => [
                'professions' => Profession::options(),
                'levels' => CourseLevel::options(),
            ],
        ]);
    }

    public function show(
        StudentProfile $studentProfile,
        BatchTransferService $transfers,
        LanguageProgressPresenter $progress,
    ): Response {
        /** @var User $viewer */
        $viewer = Auth::user();

        $this->ensureTeacherCanAccess($viewer, $studentProfile);

        abort_unless(
            $viewer->isTeacher() || $viewer->isAdmin() || $viewer->isRecruitmentManager() || StudentProfile::query()->listedForRecruiters()->registeredForPlacement()->whereKey($studentProfile->id)->exists(),
            404,
        );

        $studentProfile->load('user.batchEnrollment.batch.teacher', 'user.batchEnrollment.batch.center');

        if (! $studentProfile->isFullyViewableBy($viewer)) {
            return Inertia::render('app/recruiter/students/show', [
                'restricted' => true,
                'summary' => RecruiterCandidatePresenter::card($studentProfile),
                'profileId' => $studentProfile->id,
            ]);
        }

        $enrollment = $studentProfile->user->batchEnrollment;
        $batch = $enrollment?->batch;

        $assignment = null;
        $transferTargets = [];

        if ($batch !== null) {
            $assignment = [
                'batch_id' => $batch->id,
                'batch_name' => $batch->name,
                'center_name' => $batch->center?->name,
                'language' => $batch->language->value,
                'language_label' => $batch->language->label(),
                'level' => $batch->level->value,
                'teacher_name' => $batch->teacher?->name,
            ];

            if ($viewer->isTeacher()) {
                $transferTargets = $transfers->eligibleTargets($batch);
            }
        }

        return Inertia::render('app/recruiter/students/show', [
            'restricted' => false,
            'summary' => RecruiterCandidatePresenter::card($studentProfile),
            'cv' => CvProfilePresenter::present($studentProfile->user, $studentProfile),
            'profileId' => $studentProfile->id,
            'assignment' => $assignment,
            'language' => $progress->present($studentProfile->user),
            'transferTargets' => $transferTargets,
            'canTransfer' => $viewer->isTeacher() && $assignment !== null,
            'shortlistProjects' => $viewer->isTeacher() ? null : $this->shortlistProjects($viewer, $studentProfile),
        ]);
    }

    public function translate(
        Request $request,
        StudentProfile $studentProfile,
        CvTranslationService $translations,
    ): JsonResponse {
        /** @var User $viewer */
        $viewer = Auth::user();

        $this->ensureTeacherCanAccess($viewer, $studentProfile);
        abort_if(($viewer->isRecruiter() || $viewer->isEmployee()) && ! $studentProfile->wantsPlacement(), 404);
        abort_unless($studentProfile->isFullyViewableBy($viewer), 403);

        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:de'],
        ]);

        $studentProfile->load('user');

        try {
            $cv = $translations->translateForPrint(
                $studentProfile->user,
                $studentProfile,
                $validated['locale'],
            );
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();

            if (str_contains($message, 'not configured')) {
                abort(422, 'German CV translation is not configured.');
            }

            abort(502, 'Unable to prepare the German CV right now. Please try again.');
        }

        return response()->json([
            'cv' => $cv,
        ]);
    }

    /**
     * @return list<array{id: int, name: string, added: bool}>
     */
    private function shortlistProjects(User $viewer, StudentProfile $studentProfile): array
    {
        return $viewer->recruiterProjects()
            ->withExists(['candidates as added' => fn ($q) => $q->whereKey($studentProfile->id)])
            ->orderBy('name')
            ->get()
            ->map(fn ($project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'added' => (bool) $project->added,
            ])
            ->all();
    }

    private function ensureTeacherCanAccess(User $viewer, StudentProfile $studentProfile): void
    {
        if (! $viewer->isTeacher()) {
            return;
        }

        $ownsStudent = BatchEnrollment::query()
            ->where('student_id', $studentProfile->user_id)
            ->whereHas('batch', fn ($q) => $q->where('teacher_id', $viewer->id))
            ->exists();

        if (! $ownsStudent) {
            abort(403);
        }
    }
}
