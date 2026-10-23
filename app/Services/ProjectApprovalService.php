<?php

namespace App\Services;

use App\Enums\RequirementReviewStatus;
use App\Enums\UserRole;
use App\Models\ProjectRequirement;
use App\Models\RecruiterProjectCandidate;
use App\Models\User;
use App\Notifications\ProjectFilesRejected;
use App\Notifications\ProjectFilesResubmitted;
use App\Notifications\ProjectFilesSentToRecruiter;
use App\Notifications\StudentApproved;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class ProjectApprovalService
{
    /**
     * BV hands a student's finished documents to the recruiter for review.
     *
     * @throws DomainException
     */
    public function sendToRecruiter(RecruiterProjectCandidate $entry): void
    {
        $entry->load(['requirements', 'studentProfile', 'project.owner']);

        if ($entry->isSentToRecruiter()) {
            return;
        }

        if (! $entry->project->isFinalized()) {
            throw new DomainException('The project has not been finalized yet.');
        }

        if (! $entry->isReadyForApproval()) {
            throw new DomainException('Every requirement needs an uploaded document before sending.');
        }

        if ($entry->studentProfile->isHired()) {
            throw new DomainException('This candidate has already been placed.');
        }

        $entry->update(['sent_to_recruiter_at' => now()]);

        $entry->project->owner->notify(new ProjectFilesSentToRecruiter($entry));
    }

    /**
     * Record the recruiter's decision on one or more files in a single step.
     * Rejecting a file needs a note; a sample file is optional. The student is accepted once every file is approved.
     *
     * @param  array<int, array{approve: bool, note?: string|null, sample?: UploadedFile|null}>  $decisions  keyed by requirement id
     *
     * @throws DomainException
     */
    public function reviewFiles(RecruiterProjectCandidate $entry, User $recruiter, array $decisions): void
    {
        $entry->load(['requirements', 'studentProfile', 'project.owner']);

        if (! $entry->isSentToRecruiter()) {
            throw new DomainException('These documents have not been sent for review yet.');
        }

        if ($entry->isApproved()) {
            throw new DomainException('This candidate has already been approved.');
        }

        $requirements = $entry->requirements->keyBy('id');

        foreach ($decisions as $id => $decision) {
            $requirement = $requirements->get($id) ?? throw new DomainException('Unknown document.');

            if (! $requirement->isUploaded()) {
                throw new DomainException('"'.$requirement->title.'" has no file to review.');
            }

            if (! $decision['approve'] && blank($decision['note'] ?? null)) {
                throw new DomainException('Add a note explaining what is wrong with "'.$requirement->title.'".');
            }
        }

        $rejected = false;

        DB::transaction(function () use ($decisions, $requirements, $recruiter, $entry, &$rejected): void {
            foreach ($decisions as $id => $decision) {
                /** @var ProjectRequirement $requirement */
                $requirement = $requirements->get($id);
                $requirement->deleteSample();

                if ($decision['approve']) {
                    $requirement->update([
                        'review_status' => RequirementReviewStatus::Approved,
                        'rejection_note' => null,
                        'sample_path' => null,
                        'sample_name' => null,
                        'reviewed_at' => now(),
                        'reviewed_by' => $recruiter->id,
                    ]);

                    continue;
                }

                $rejected = true;
                $sample = $decision['sample'] ?? null;

                $requirement->update([
                    'review_status' => RequirementReviewStatus::Rejected,
                    'rejection_note' => trim((string) ($decision['note'] ?? '')),
                    'sample_path' => $sample?->store("project-samples/{$entry->recruiter_project_id}/{$entry->id}", ProjectRequirement::DISK),
                    'sample_name' => $sample === null ? null : Str::limit($sample->getClientOriginalName(), 200, ''),
                    'reviewed_at' => now(),
                    'reviewed_by' => $recruiter->id,
                ]);
            }
        });

        $entry->unsetRelation('requirements')->load('requirements');

        if ($entry->allFilesApproved()) {
            $this->accept($entry, $recruiter);

            return;
        }

        if ($rejected) {
            $this->notifyStaff(new ProjectFilesRejected($entry));
        }
    }

    /**
     * Approve every file of a student at once.
     *
     * @throws DomainException
     */
    public function approveAll(RecruiterProjectCandidate $entry, User $recruiter): void
    {
        $entry->load('requirements');

        $this->reviewFiles($entry, $recruiter, $entry->requirements->mapWithKeys(
            fn (ProjectRequirement $requirement): array => [$requirement->id => ['approve' => true]],
        )->all());
    }

    /**
     * BV puts a file (or clears it) on a requirement. Returns true when it answered a rejection.
     */
    public function replaceFile(ProjectRequirement $requirement, ?string $path, ?string $originalName, User $uploader): bool
    {
        $requirement->deleteFile();
        $requirement->update([
            'file_path' => $path,
            'original_name' => $path === null ? null : ($originalName ?? basename($path)),
            'uploaded_by' => $path === null ? null : $uploader->id,
            'uploaded_at' => $path === null ? null : now(),
        ]);

        return $this->fileReplaced($requirement);
    }

    /**
     * BV replaced a file on a sent entry: it needs a fresh review. Returns true when it answered a rejection.
     */
    public function fileReplaced(ProjectRequirement $requirement): bool
    {
        $entry = $requirement->entry;

        if (! $entry->isSentToRecruiter() || $entry->isApproved()) {
            return false;
        }

        $wasRejected = $requirement->isRejected();

        $requirement->deleteSample();
        $requirement->update([
            'review_status' => RequirementReviewStatus::Pending,
            'rejection_note' => null,
            'sample_path' => null,
            'sample_name' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
        ]);

        return $wasRejected;
    }

    /**
     * Tell the recruiter once BV has dealt with every rejected file.
     */
    public function notifyResubmitted(RecruiterProjectCandidate $entry): void
    {
        $entry->unsetRelation('requirements')->load(['requirements', 'project.owner', 'studentProfile.user']);

        if ($entry->isSentToRecruiter() && ! $entry->isApproved() && ! $entry->hasRejectedFiles()) {
            $entry->project->owner->notify(new ProjectFilesResubmitted($entry));
        }
    }

    private function accept(RecruiterProjectCandidate $entry, User $recruiter): void
    {
        $entry->update(['approved_at' => now(), 'approved_by' => $recruiter->id]);

        $this->notifyStaff(new StudentApproved($entry));
    }

    private function notifyStaff(object $notification): void
    {
        Notification::send(User::query()->whereIn('role', UserRole::placementStaff())->get(), $notification);
    }
}
