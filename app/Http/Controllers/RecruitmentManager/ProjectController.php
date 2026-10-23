<?php

namespace App\Http\Controllers\RecruitmentManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecruitmentManager\MessageProjectStudentsRequest;
use App\Http\Requests\RecruitmentManager\UploadProjectDocumentRequest;
use App\Models\ProjectRequirement;
use App\Models\RecruiterProject;
use App\Models\RecruiterProjectCandidate;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ProjectApprovalService;
use App\Services\ProjectStudentMessagingService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        $projects = RecruiterProject::query()
            ->whereNotNull('finalized_at')
            ->with('owner:id,name')
            ->withCount([
                'entries as students_count',
                'entries as sent_count' => fn ($q) => $q->whereNotNull('sent_to_recruiter_at'),
                'entries as approved_count' => fn ($q) => $q->whereNotNull('approved_at'),
            ])
            ->orderByDesc('recruiter_updated_at')
            ->latest('finalized_at')
            ->get()
            ->map(fn (RecruiterProject $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'recruiter' => $project->owner->name,
                'country' => $project->country,
                'sector' => $project->sector,
                'timeline' => $project->timeline,
                'students_count' => $project->students_count,
                'sent_count' => $project->sent_count,
                'approved_count' => $project->approved_count,
                'updated_by_recruiter' => $project->recruiter_updated_at !== null,
                'finalized_at' => $project->finalized_at?->toDateTimeString(),
            ]);

        return Inertia::render('app/recruitment-manager/projects/index', ['projects' => $projects]);
    }

    public function show(RecruiterProject $project, ProjectStudentMessagingService $messaging): Response
    {
        abort_unless($project->isFinalized(), 404);

        $project->load('owner:id,name,email');

        $entries = $project->entries()
            ->with(['requirements', 'studentProfile.user'])
            ->get()
            ->map(fn (RecruiterProjectCandidate $entry): array => [
                'student_profile_id' => $entry->student_profile_id,
                'name' => $entry->studentProfile->user->name,
                'student_number' => $entry->studentProfile->user->student_number,
                'status' => $entry->statusFor($project),
                'uploaded' => $entry->uploadedCount(),
                'total' => $entry->requirements->count(),
                'can_send' => ! $entry->isSentToRecruiter() && $entry->isReadyForApproval(),
                'locked' => $entry->isApproved(),
                'congratulated_at' => $entry->congratulated_at?->toDateTimeString(),
                'requirements_sent_at' => $entry->requirements_sent_at?->toDateTimeString(),
                'requirements' => $entry->requirements->map(fn (ProjectRequirement $requirement): array => [
                    'id' => $requirement->id,
                    'title' => $requirement->title,
                    'file_name' => $requirement->original_name,
                    'download_url' => $requirement->isUploaded() ? route('project-documents.download', $requirement) : null,
                    'view_url' => $requirement->isUploaded() ? route('project-documents.view', $requirement) : null,
                    'review_status' => $requirement->review_status->value,
                    'rejection_note' => $requirement->rejection_note,
                    'sample_name' => $requirement->sample_name,
                    'sample_url' => $requirement->hasSample() ? route('project-samples.download', $requirement) : null,
                    'sample_view_url' => $requirement->hasSample() ? route('project-samples.view', $requirement) : null,
                ])->values()->all(),
            ]);

        return Inertia::render('app/recruitment-manager/projects/show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'recruiter' => $project->owner->name,
                'recruiter_email' => $project->owner->email,
                'country' => $project->country,
                'sector' => $project->sector,
                'timeline' => $project->timeline,
                'stage' => $project->stage(),
                'finalized_at' => $project->finalized_at?->toDateTimeString(),
                'updated_by_recruiter' => $project->recruiter_updated_at !== null,
            ],
            'entries' => $entries,
            'defaults' => [
                'congratulate' => $messaging->defaultCongratulation($project),
                'requirements' => $messaging->defaultRequirementsIntro($project),
            ],
        ]);
    }

    public function upload(
        UploadProjectDocumentRequest $request,
        RecruiterProject $project,
        ProjectRequirement $requirement,
        ProjectApprovalService $approvals,
    ): RedirectResponse {
        $entry = $requirement->entry;

        abort_unless($entry->recruiter_project_id === $project->id && $project->isFinalized(), 404);
        abort_if($entry->isApproved(), 422, 'This candidate is already approved.');

        $project->loadMissing('owner:id,name');

        $path = $request->file('file')->store("project-documents/{$project->id}/{$entry->id}", ProjectRequirement::DISK);
        abort_if($path === false, 500, 'The file could not be saved.');

        $answeredRejection = $approvals->replaceFile($requirement, $path, $request->file('file')->getClientOriginalName(), $this->manager());

        if ($answeredRejection) {
            $approvals->notifyResubmitted($entry);
        }

        return back()->with('success', $answeredRejection
            ? "Updated file saved. {$project->owner->name} has been told to review it again."
            : "{$requirement->title} saved.");
    }

    public function send(RecruiterProject $project, StudentProfile $studentProfile, ProjectApprovalService $approvals): RedirectResponse
    {
        $entry = $project->entries()->where('student_profile_id', $studentProfile->id)->firstOrFail();
        $project->loadMissing('owner:id,name');

        try {
            $approvals->sendToRecruiter($entry);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['send' => $exception->getMessage()]);
        }

        return back()->with('success', "Sent to {$project->owner->name}. They have been notified in the app, by email and on WhatsApp if they have a number.");
    }

    public function message(MessageProjectStudentsRequest $request, RecruiterProject $project, ProjectStudentMessagingService $messaging): RedirectResponse
    {
        $entries = $project->entries()
            ->when($request->validated('student_profile_id'), fn ($q, int $id) => $q->where('student_profile_id', $id))
            ->with(['requirements', 'studentProfile.user'])
            ->get();

        $result = $request->validated('type') === 'congratulate'
            ? $messaging->congratulate($entries, $request->validated('message'))
            : $messaging->sendRequirements($entries, $request->validated('message'));

        return back()->with('success', "Sent to {$result['sent']} ".($result['sent'] === 1 ? 'student' : 'students').'.');
    }

    private function manager(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
