<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruiter\ProjectRequirementsRequest;
use App\Models\RecruiterProject;
use App\Services\ProjectRequirementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ProjectRequirementController extends Controller
{
    public function update(
        ProjectRequirementsRequest $request,
        RecruiterProject $project,
        ProjectRequirementService $requirements,
    ): RedirectResponse {
        abort_unless($project->user_id === Auth::id(), 404);

        $requirements->save(
            $project,
            $request->validated('requirements'),
            $request->validated('students') ?? [],
        );
        $requirements->touch($project);

        return back();
    }

    public function finalize(
        ProjectRequirementsRequest $request,
        RecruiterProject $project,
        ProjectRequirementService $requirements,
    ): RedirectResponse {
        abort_unless($project->user_id === Auth::id(), 404);
        abort_if($project->isFinalized(), 422, 'This project is already finalized.');
        abort_unless($project->entries()->exists(), 422, 'Add at least one candidate before finalizing.');

        $requirements->save(
            $project,
            $request->validated('requirements'),
            $request->validated('students') ?? [],
        );
        $requirements->finalize($project);

        return back();
    }
}
