<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruiter\ProjectRequest;
use App\Models\ProjectRequirement;
use App\Models\RecruiterProject;
use App\Models\RecruiterProjectCandidate;
use App\Models\User;
use App\Support\RecruiterCandidatePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        $projects = $this->owner()->recruiterProjects()
            ->withCount([
                'entries as candidates_count',
                'entries as approved_count' => fn ($q) => $q->whereNotNull('approved_at'),
            ])
            ->latest()
            ->get()
            ->map(fn (RecruiterProject $project): array => $this->summary($project));

        return Inertia::render('app/recruiter/projects/index', ['projects' => $projects]);
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $project = $this->owner()->recruiterProjects()->create($request->validated());

        return to_route('recruiter.projects.show', $project);
    }

    public function show(RecruiterProject $project): Response
    {
        $this->authorizeOwner($project);

        $entries = $project->entries()
            ->with(['requirements', 'studentProfile.user.batchEnrollment.batch'])
            ->whereHas('studentProfile', fn ($q) => $q->listedForRecruiters())
            ->latest()
            ->get()
            ->map(fn (RecruiterProjectCandidate $entry): array => [
                ...RecruiterCandidatePresenter::card($entry->studentProfile, honorApproval: true),
                'entry' => $this->entrySummary($project, $entry),
            ]);

        return Inertia::render('app/recruiter/projects/show', [
            'project' => $this->summary($project),
            'candidates' => $entries,
            'otherProjects' => $this->owner()->recruiterProjects()
                ->whereKeyNot($project->id)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(ProjectRequest $request, RecruiterProject $project): RedirectResponse
    {
        $this->authorizeOwner($project);

        $project->update($request->validated());

        return back();
    }

    public function destroy(RecruiterProject $project): RedirectResponse
    {
        $this->authorizeOwner($project);

        abort_if($project->entries()->whereNotNull('approved_at')->exists(), 422, 'Projects with approved candidates cannot be deleted.');

        $project->entries()->with('requirements')->get()
            ->each(fn ($entry) => $entry->requirements->each->delete());

        $project->delete();

        return to_route('recruiter.projects.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(RecruiterProject $project): array
    {
        $total = $project->candidates_count ?? $project->entries()->count();
        $approved = $project->approved_count ?? $project->entries()->whereNotNull('approved_at')->count();

        return [
            'id' => $project->id,
            'name' => $project->name,
            'country' => $project->country,
            'sector' => $project->sector,
            'timeline' => $project->timeline,
            'candidates_count' => $total,
            'approved_count' => $approved,
            'finalized' => $project->isFinalized(),
            'stage' => match (true) {
                ! $project->isFinalized() => 'draft',
                $total > 0 && $approved === $total => 'approved',
                default => 'in_progress',
            },
            'requirement_template' => $project->requirement_template ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function entrySummary(RecruiterProject $project, RecruiterProjectCandidate $entry): array
    {
        $visible = $entry->isSentToRecruiter();

        return [
            'status' => $entry->statusFor($project),
            'uploaded' => $entry->uploadedCount(),
            'total' => $entry->requirements->count(),
            'approved_at' => $entry->approved_at?->toDateString(),
            'requirements' => $entry->requirements->map(fn (ProjectRequirement $requirement): array => [
                'id' => $requirement->id,
                'title' => $requirement->title,
                'uploaded' => $requirement->isUploaded(),
                'file_name' => $visible ? $requirement->original_name : null,
                'download_url' => $visible && $requirement->isUploaded()
                    ? route('project-documents.download', $requirement)
                    : null,
                'view_url' => $visible && $requirement->isUploaded()
                    ? route('project-documents.view', $requirement)
                    : null,
                'review_status' => $requirement->review_status->value,
                'rejection_note' => $requirement->rejection_note,
                'sample_name' => $requirement->sample_name,
                'sample_url' => $requirement->hasSample() ? route('project-samples.download', $requirement) : null,
                'sample_view_url' => $requirement->hasSample() ? route('project-samples.view', $requirement) : null,
            ])->values()->all(),
        ];
    }

    private function owner(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    private function authorizeOwner(RecruiterProject $project): void
    {
        abort_unless($project->user_id === Auth::id(), 404);
    }
}
