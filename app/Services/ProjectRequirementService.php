<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\ProjectRequirement;
use App\Models\RecruiterProject;
use App\Models\RecruiterProjectCandidate;
use App\Models\User;
use App\Notifications\ProjectFinalized;
use App\Notifications\ProjectUpdatedByRecruiter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ProjectRequirementService
{
    /**
     * @param  array<int, mixed>  $titles
     * @return list<string>
     */
    public function normalize(array $titles): array
    {
        $seen = [];

        foreach ($titles as $title) {
            $clean = trim((string) $title);

            if ($clean !== '' && ! in_array(mb_strtolower($clean), array_map('mb_strtolower', $seen), true)) {
                $seen[] = mb_substr($clean, 0, 255);
            }
        }

        return $seen;
    }

    /**
     * Make an unapproved entry's requirements match the given titles (keeps uploads of kept titles).
     *
     * @param  list<string>  $titles
     */
    public function syncEntry(RecruiterProjectCandidate $entry, array $titles): void
    {
        if ($entry->isApproved()) {
            return;
        }

        $titles = $this->normalize($titles);
        $existing = $entry->requirements()->get();

        foreach ($existing as $requirement) {
            if (! in_array(mb_strtolower($requirement->title), array_map('mb_strtolower', $titles), true)) {
                $requirement->delete();
            }
        }

        $kept = $existing->pluck('title')->map(fn (string $t): string => mb_strtolower($t))->all();

        foreach ($titles as $title) {
            if (! in_array(mb_strtolower($title), $kept, true)) {
                $entry->requirements()->create(['title' => $title]);
            }
        }

        $entry->unsetRelation('requirements');
    }

    /**
     * Save the project-wide template and, per student, an optional override list (profile id => titles).
     *
     * @param  list<string>  $template
     * @param  array<int|string, array<int, mixed>>  $perStudent
     */
    public function save(RecruiterProject $project, array $template, array $perStudent = []): void
    {
        DB::transaction(function () use ($project, $template, $perStudent): void {
            $template = $this->normalize($template);
            $project->update(['requirement_template' => $template]);

            foreach ($project->entries()->with('requirements')->get() as $entry) {
                $override = $perStudent[$entry->student_profile_id] ?? null;
                $this->syncEntry($entry, is_array($override) ? $override : $this->templateFor($entry, $template));
            }
        });
    }

    public function finalize(RecruiterProject $project): void
    {
        $project->forceFill(['finalized_at' => now(), 'recruiter_updated_at' => null])->save();

        $this->notifyAdmins(new ProjectFinalized($project));
    }

    /**
     * Give a newly added student the project template.
     */
    public function applyTemplate(RecruiterProjectCandidate $entry): void
    {
        $this->syncEntry($entry, $entry->project->requirement_template ?? []);
    }

    /**
     * Flag a finalized project as changed by the recruiter; admins are told once per review cycle.
     */
    public function touch(RecruiterProject $project): void
    {
        if (! $project->isFinalized()) {
            return;
        }

        $firstChange = $project->recruiter_updated_at === null;
        $project->forceFill(['recruiter_updated_at' => now()])->save();

        if ($firstChange) {
            $this->notifyAdmins(new ProjectUpdatedByRecruiter($project));
        }
    }

    public function removeEntry(RecruiterProjectCandidate $entry): void
    {
        $entry->requirements->each(fn (ProjectRequirement $requirement) => $requirement->delete());
        $entry->delete();
    }

    /**
     * @param  list<string>  $template
     * @return list<string>
     */
    private function templateFor(RecruiterProjectCandidate $entry, array $template): array
    {
        return $template;
    }

    private function notifyAdmins(object $notification): void
    {
        Notification::send(User::query()->whereIn('role', UserRole::placementStaff())->get(), $notification);
    }
}
