<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\RecruiterProject;
use App\Models\StudentProfile;
use App\Services\ProjectRequirementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProjectCandidateController extends Controller
{
    public function store(RecruiterProject $project, StudentProfile $studentProfile, ProjectRequirementService $requirements): RedirectResponse
    {
        $this->authorizeOwner($project);

        abort_if($studentProfile->isHired(), 422, 'Hired candidates cannot be shortlisted.');
        abort_unless(StudentProfile::query()->listedForRecruiters()->registeredForPlacement()->whereKey($studentProfile->id)->exists(), 404);

        $project->candidates()->syncWithoutDetaching([$studentProfile->id]);

        if ($project->isFinalized()) {
            $requirements->applyTemplate($project->entries()->where('student_profile_id', $studentProfile->id)->firstOrFail());
            $requirements->touch($project);
        }

        return back();
    }

    public function destroy(RecruiterProject $project, StudentProfile $studentProfile, ProjectRequirementService $requirements): RedirectResponse
    {
        $this->authorizeOwner($project);

        $entry = $project->entries()->where('student_profile_id', $studentProfile->id)->first();

        if ($entry !== null) {
            abort_if($entry->isApproved(), 422, 'Approved candidates cannot be removed.');
            $requirements->removeEntry($entry);
            $requirements->touch($project);
        }

        return back();
    }

    public function move(Request $request, RecruiterProject $project, StudentProfile $studentProfile, ProjectRequirementService $requirements): RedirectResponse
    {
        $this->authorizeOwner($project);

        $validated = $request->validate([
            'target_project_id' => [
                'required',
                'integer',
                Rule::exists('recruiter_projects', 'id')->where('user_id', Auth::id()),
                Rule::notIn([$project->id]),
            ],
        ]);

        $entry = $project->entries()->where('student_profile_id', $studentProfile->id)->first();
        abort_if($entry?->isApproved(), 422, 'Approved candidates cannot be moved.');

        $target = RecruiterProject::query()->findOrFail($validated['target_project_id']);
        $target->candidates()->syncWithoutDetaching([$studentProfile->id]);

        if ($target->isFinalized()) {
            $requirements->applyTemplate($target->entries()->where('student_profile_id', $studentProfile->id)->firstOrFail());
            $requirements->touch($target);
        }

        if ($entry !== null) {
            $requirements->removeEntry($entry);
            $requirements->touch($project);
        }

        return back();
    }

    private function authorizeOwner(RecruiterProject $project): void
    {
        abort_unless($project->user_id === Auth::id(), 404);
    }
}
