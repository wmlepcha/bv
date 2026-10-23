<?php

namespace Tests\Feature\Recruiter;

use App\Models\ProjectRequirement;
use App\Models\RecruiterProject;
use App\Models\RecruiterProjectCandidate;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\ProjectFilesSentToRecruiter;
use App\Notifications\ProjectFinalized;
use App\Notifications\ProjectUpdatedByRecruiter;
use App\Notifications\StudentApproved;
use App\Services\ProjectApprovalService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectFinalizeTest extends TestCase
{
    use RefreshDatabase;

    private User $recruiter;

    private RecruiterProject $project;

    private StudentProfile $a;

    private StudentProfile $b;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(ProjectRequirement::DISK);
        Notification::fake();

        $this->recruiter = User::factory()->recruiter()->create();
        $this->project = RecruiterProject::factory()->create(['user_id' => $this->recruiter->id]);
        $this->a = StudentProfile::factory()->forPlacement()->enrolled()->create();
        $this->b = StudentProfile::factory()->forPlacement()->enrolled()->create();
        $this->project->candidates()->attach([$this->a->id, $this->b->id]);
    }

    private function entry(StudentProfile $profile): RecruiterProjectCandidate
    {
        return $this->project->entries()->where('student_profile_id', $profile->id)->firstOrFail();
    }

    private function upload(RecruiterProjectCandidate $entry): void
    {
        foreach ($entry->requirements as $requirement) {
            $path = "project-documents/{$entry->recruiter_project_id}/{$entry->id}/{$requirement->id}.pdf";
            Storage::disk(ProjectRequirement::DISK)->put($path, 'pdf');
            $requirement->update(['file_path' => $path, 'original_name' => 'doc.pdf', 'uploaded_at' => now()]);
        }
    }

    private function accept(RecruiterProjectCandidate $entry): void
    {
        $service = app(ProjectApprovalService::class);
        $service->sendToRecruiter($entry);
        $service->approveAll($entry->fresh(), $this->recruiter);
    }

    public function test_finalize_copies_template_to_students_with_overrides_and_notifies_admins(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($this->recruiter)
            ->post(route('recruiter.projects.finalize', $this->project), [
                'requirements' => ['Passport copy', 'Degree certificate'],
                'students' => [$this->b->id => ['Passport copy', 'Nursing licence', 'passport copy']],
            ])
            ->assertRedirect();

        $this->assertTrue($this->project->fresh()->isFinalized());
        $this->assertSame(['Passport copy', 'Degree certificate'], $this->entry($this->a)->requirements->pluck('title')->all());
        $this->assertSame(['Passport copy', 'Nursing licence'], $this->entry($this->b)->requirements->pluck('title')->all());
        Notification::assertSentTo($admin, ProjectFinalized::class);
    }

    public function test_finalize_needs_students_and_requirements_and_only_once(): void
    {
        $empty = RecruiterProject::factory()->create(['user_id' => $this->recruiter->id]);
        $this->actingAs($this->recruiter);

        $this->post(route('recruiter.projects.finalize', $empty), ['requirements' => ['x']])->assertStatus(422);
        $this->post(route('recruiter.projects.finalize', $this->project), ['requirements' => []])->assertSessionHasErrors('requirements');

        $this->post(route('recruiter.projects.finalize', $this->project), ['requirements' => ['x']])->assertRedirect();
        $this->post(route('recruiter.projects.finalize', $this->project), ['requirements' => ['x']])->assertStatus(422);
    }

    public function test_other_recruiters_cannot_finalize_or_edit_requirements(): void
    {
        $this->actingAs(User::factory()->recruiter()->create())
            ->post(route('recruiter.projects.finalize', $this->project), ['requirements' => ['x']])
            ->assertNotFound();
    }

    public function test_adding_and_removing_after_finalize_updates_requirements_and_notifies_once(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($this->recruiter)
            ->post(route('recruiter.projects.finalize', $this->project), ['requirements' => ['Passport copy']]);

        $c = StudentProfile::factory()->forPlacement()->enrolled()->create();
        $this->post(route('recruiter.projects.candidates.store', [$this->project, $c]))->assertRedirect();
        $this->assertSame(['Passport copy'], $this->entry($c)->requirements->pluck('title')->all());

        $this->delete(route('recruiter.projects.candidates.destroy', [$this->project, $c]));
        $this->assertDatabaseMissing('project_requirements', ['title' => 'Passport copy', 'recruiter_project_candidate_id' => null]);
        $this->assertSame(2, $this->project->entries()->count());

        Notification::assertSentToTimes($admin, ProjectUpdatedByRecruiter::class, 1);
    }

    public function test_requirement_edit_keeps_uploads_of_kept_items(): void
    {
        $this->actingAs($this->recruiter)
            ->post(route('recruiter.projects.finalize', $this->project), ['requirements' => ['Passport copy', 'Degree']]);
        $entry = $this->entry($this->a);
        $this->upload($entry);

        $this->put(route('recruiter.projects.requirements.update', $this->project), [
            'requirements' => ['Passport copy', 'Police clearance'],
        ])->assertRedirect();

        $titles = $this->entry($this->a)->requirements->keyBy('title');
        $this->assertSame(['Passport copy', 'Police clearance'], $titles->keys()->all());
        $this->assertTrue($titles['Passport copy']->isUploaded());
        $this->assertFalse($titles['Police clearance']->isUploaded());
    }

    public function test_sending_requires_all_documents_and_acceptance_needs_every_file_approved_by_recruiter(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($this->recruiter)
            ->post(route('recruiter.projects.finalize', $this->project), ['requirements' => ['Passport copy', 'Degree']]);
        $entry = $this->entry($this->a);
        $service = app(ProjectApprovalService::class);

        try {
            $service->sendToRecruiter($entry);
            $this->fail('Expected sending to be refused');
        } catch (DomainException) {
            $this->assertFalse($entry->fresh()->isSentToRecruiter());
        }

        $this->upload($entry);
        $service->sendToRecruiter($entry->fresh());
        Notification::assertSentTo($this->recruiter, ProjectFilesSentToRecruiter::class);
        $this->assertFalse($entry->fresh()->isApproved());

        $first = $entry->requirements()->first();
        $service->reviewFiles($entry->fresh(), $this->recruiter, [$first->id => ['approve' => true]]);
        $this->assertFalse($entry->fresh()->isApproved());

        $service->approveAll($entry->fresh(), $this->recruiter);

        $this->assertTrue($entry->fresh()->isApproved());
        $this->assertFalse($this->a->fresh()->isHired());
        Notification::assertSentTo($admin, StudentApproved::class);
    }

    public function test_approved_candidate_cannot_be_removed_and_a_placed_candidate_cannot_be_sent_by_another_project(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($this->recruiter)
            ->post(route('recruiter.projects.finalize', $this->project), ['requirements' => ['Passport copy']]);
        $entry = $this->entry($this->a);
        $this->upload($entry);
        $this->accept($entry->fresh());

        $this->delete(route('recruiter.projects.candidates.destroy', [$this->project, $this->a]))->assertStatus(422);

        $other = RecruiterProject::factory()->create(['finalized_at' => now()]);
        $otherEntry = RecruiterProjectCandidate::create(['recruiter_project_id' => $other->id, 'student_profile_id' => $this->a->id]);
        $otherEntry->requirements()->create(['title' => 'x', 'file_path' => 'p', 'original_name' => 'p']);
        $this->a->update(['placement_status' => 'hired']);

        $this->expectException(DomainException::class);
        app(ProjectApprovalService::class)->sendToRecruiter($otherEntry->fresh());
    }

    public function test_owner_sees_approved_candidate_and_documents_others_stay_locked(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($this->recruiter)
            ->post(route('recruiter.projects.finalize', $this->project), ['requirements' => ['Passport copy']]);
        $entry = $this->entry($this->a);
        $this->upload($entry);
        $this->accept($entry->fresh());
        $requirement = $entry->requirements()->first();
        $this->a->update(['placement_status' => 'hired']);

        $this->get(route('recruiter.projects.show', $this->project))
            ->assertInertia(fn (Assert $page) => $page
                ->where('project.stage', 'in_progress')
                ->where('project.approved_count', 1)
                ->where('candidates', fn ($candidates) => collect($candidates)->firstWhere('id', $this->a->id)['viewable'] === true
                    && collect($candidates)->firstWhere('id', $this->a->id)['entry']['status'] === 'approved'));

        $this->get(route('recruiter.students.index'))
            ->assertInertia(fn (Assert $page) => $page->where('profiles.data', fn ($cards) => collect($cards)->firstWhere('id', $this->a->id)['viewable'] === false));

        $this->get(route('recruiter.students.show', $this->a))
            ->assertInertia(fn (Assert $page) => $page->where('restricted', false));
        $this->get(route('project-documents.download', $requirement))->assertOk();

        $stranger = User::factory()->recruiter()->create();
        $this->actingAs($stranger)->get(route('recruiter.students.show', $this->a))
            ->assertInertia(fn (Assert $page) => $page->where('restricted', true));
        $this->get(route('project-documents.download', $requirement))->assertNotFound();

        $this->actingAs($admin)->get(route('project-documents.download', $requirement))->assertOk();
    }

    public function test_documents_are_not_downloadable_before_approval(): void
    {
        $this->actingAs($this->recruiter)
            ->post(route('recruiter.projects.finalize', $this->project), ['requirements' => ['Passport copy']]);
        $entry = $this->entry($this->a);
        $this->upload($entry);

        $this->get(route('project-documents.download', $entry->requirements()->first()))->assertNotFound();
    }

    public function test_stage_is_draft_in_progress_then_approved(): void
    {
        $admin = User::factory()->admin()->create();
        $this->assertSame('draft', $this->project->stage());

        $this->actingAs($this->recruiter)
            ->post(route('recruiter.projects.finalize', $this->project), ['requirements' => ['Passport copy']]);
        $this->assertSame('in_progress', $this->project->fresh()->stage());

        foreach ([$this->a, $this->b] as $profile) {
            $entry = $this->entry($profile);
            $this->upload($entry);
            $this->accept($entry->fresh());
        }

        $this->assertSame('approved', $this->project->fresh()->stage());
    }

    public function test_deleting_a_project_removes_uploaded_files_but_not_when_a_student_is_approved(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($this->recruiter)
            ->post(route('recruiter.projects.finalize', $this->project), ['requirements' => ['Passport copy']]);
        $entryA = $this->entry($this->a);
        $this->upload($entryA);
        $path = $entryA->requirements()->first()->file_path;

        $this->accept($entryA->fresh());
        $this->delete(route('recruiter.projects.destroy', $this->project))->assertStatus(422);
        $this->assertModelExists($this->project);

        $other = RecruiterProject::factory()->create(['user_id' => $this->recruiter->id]);
        $entry = RecruiterProjectCandidate::create(['recruiter_project_id' => $other->id, 'student_profile_id' => $this->b->id]);
        $requirement = $entry->requirements()->create(['title' => 'x', 'file_path' => 'project-documents/x.pdf', 'original_name' => 'x.pdf']);
        Storage::disk(ProjectRequirement::DISK)->put('project-documents/x.pdf', 'pdf');

        $this->delete(route('recruiter.projects.destroy', $other))->assertRedirect(route('recruiter.projects.index'));
        $this->assertModelMissing($other);
        Storage::disk(ProjectRequirement::DISK)->assertMissing('project-documents/x.pdf');
        Storage::disk(ProjectRequirement::DISK)->assertExists($path);
    }
}
