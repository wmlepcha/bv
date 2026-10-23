<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruiter\ReviewProjectFilesRequest;
use App\Models\RecruiterProject;
use App\Models\RecruiterProjectCandidate;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ProjectApprovalService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ProjectReviewController extends Controller
{
    public function store(
        ReviewProjectFilesRequest $request,
        RecruiterProject $project,
        StudentProfile $studentProfile,
        ProjectApprovalService $approvals,
    ): RedirectResponse {
        $entry = $this->entryFor($project, $studentProfile);

        $this->run(fn () => $approvals->reviewFiles($entry, $this->recruiter(), $request->decisions()));

        return back();
    }

    public function approveAll(RecruiterProject $project, StudentProfile $studentProfile, ProjectApprovalService $approvals): RedirectResponse
    {
        $entry = $this->entryFor($project, $studentProfile);

        $this->run(fn () => $approvals->approveAll($entry, $this->recruiter()));

        return back();
    }

    private function entryFor(RecruiterProject $project, StudentProfile $studentProfile): RecruiterProjectCandidate
    {
        abort_unless($project->user_id === Auth::id(), 404);

        return $project->entries()->where('student_profile_id', $studentProfile->id)->firstOrFail();
    }

    private function recruiter(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    private function run(callable $action): void
    {
        try {
            $action();
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['review' => $exception->getMessage()]);
        }
    }
}
