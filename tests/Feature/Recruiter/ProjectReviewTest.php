<?php

namespace Tests\Feature\Recruiter;

use App\Enums\RequirementReviewStatus;
use App\Models\ProjectRequirement;
use App\Models\RecruiterProject;
use App\Models\RecruiterProjectCandidate;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\ProjectFilesRejected;
use App\Notifications\ProjectFilesResubmitted;
use App\Notifications\StudentApproved;
use App\Services\ProjectApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $recruiter;

    private User $admin;

    private RecruiterProject $project;

    private StudentProfile $student;

    private RecruiterProjectCandidate $entry;

    /** @var array<int, ProjectRequirement> */
    private array $files;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(ProjectRequirement::DISK);
        Notification::fake();
        config(['services.whatsapp.enabled' => true]);

        $this->recruiter = User::factory()->recruiter()->create();
        $this->admin = User::factory()->admin()->create(['whatsapp_number' => '+919800000010']);
        $this->project = RecruiterProject::factory()->create(['user_id' => $this->recruiter->id, 'finalized_at' => now()]);
        $this->student = StudentProfile::factory()->enrolled()->create();
        $this->entry = RecruiterProjectCandidate::create(['recruiter_project_id' => $this->project->id, 'student_profile_id' => $this->student->id]);

        foreach (['Passport', 'Degree'] as $title) {
            $path = "project-documents/{$this->project->id}/{$this->entry->id}/{$title}.pdf";
            Storage::disk(ProjectRequirement::DISK)->put($path, 'pdf');
            $this->files[] = $this->entry->requirements()->create(['title' => $title, 'file_path' => $path, 'original_name' => "{$title}.pdf", 'uploaded_at' => now()]);
        }
    }

    private function send(): void
    {
        app(ProjectApprovalService::class)->sendToRecruiter($this->entry->fresh());
    }

    private function review(array $decisions, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->recruiter)
            ->post(route('recruiter.projects.candidates.review', [$this->project, $this->student]), ['decisions' => $decisions]);
    }

    public function test_files_cannot_be_reviewed_before_they_are_sent(): void
    {
        $this->review([$this->files[0]->id => ['approve' => 1]])->assertSessionHasErrors('review');

        $this->assertSame(RequirementReviewStatus::Pending, $this->files[0]->fresh()->review_status);
    }

    public function test_rejecting_a_file_needs_a_note_and_the_sample_is_optional(): void
    {
        $this->send();

        $this->review([$this->files[0]->id => ['approve' => 0, 'note' => '']])
            ->assertSessionHasErrors("decisions.{$this->files[0]->id}.note");

        $this->review([$this->files[0]->id => ['approve' => 0, 'note' => 'Scan is blurry']])->assertSessionHasNoErrors();

        $file = $this->files[0]->fresh();
        $this->assertSame(RequirementReviewStatus::Rejected, $file->review_status);
        $this->assertSame('Scan is blurry', $file->rejection_note);
        $this->assertNull($file->sample_path);
        Notification::assertSentTo($this->admin, ProjectFilesRejected::class, fn ($n, array $channels): bool => in_array(WhatsAppChannel::class, $channels, true));
        $this->assertSame('changes_requested', $this->entry->fresh()->load('requirements')->statusFor($this->project));
    }

    public function test_rejection_can_carry_a_sample_that_staff_and_owner_can_download(): void
    {
        $this->send();

        $this->review([$this->files[1]->id => ['approve' => 0, 'note' => 'Use this layout', 'sample' => UploadedFile::fake()->create('example.pdf', 20, 'application/pdf')]])
            ->assertSessionHasNoErrors();

        $file = $this->files[1]->fresh();
        Storage::disk(ProjectRequirement::DISK)->assertExists($file->sample_path);
        $this->actingAs($this->recruiter)->get(route('project-samples.download', $file))->assertOk();
        $this->actingAs($this->admin)->get(route('project-samples.download', $file))->assertOk();
        $this->actingAs(User::factory()->recruiter()->create())->get(route('project-samples.download', $file))->assertNotFound();
    }

    public function test_changed_decision_and_replacement_flow_ends_with_acceptance_and_no_auto_hire(): void
    {
        $this->send();
        $service = app(ProjectApprovalService::class);

        $this->review([
            $this->files[0]->id => ['approve' => 1],
            $this->files[1]->id => ['approve' => 0, 'note' => 'Wrong year'],
        ]);

        // BV replaces the rejected file; recruiter hears about it once
        $replaced = $service->fileReplaced($this->files[1]->fresh()->setRelation('entry', $this->entry->fresh()));
        $this->assertTrue($replaced);
        $service->notifyResubmitted($this->entry->fresh());
        Notification::assertSentToTimes($this->recruiter, ProjectFilesResubmitted::class, 1);
        $this->assertSame(RequirementReviewStatus::Pending, $this->files[1]->fresh()->review_status);
        $this->assertNull($this->files[1]->fresh()->rejection_note);

        // recruiter flips the first file to rejected, then approves everything
        $this->review([$this->files[0]->id => ['approve' => 0, 'note' => 'Expired']]);
        $this->assertSame(RequirementReviewStatus::Rejected, $this->files[0]->fresh()->review_status);

        $this->actingAs($this->recruiter)
            ->post(route('recruiter.projects.candidates.approve-all', [$this->project, $this->student]))
            ->assertSessionHasNoErrors();

        $this->assertTrue($this->entry->fresh()->isApproved());
        $this->assertSame($this->recruiter->id, $this->entry->fresh()->approved_by);
        $this->assertFalse($this->student->fresh()->isHired());
        Notification::assertSentTo($this->admin, StudentApproved::class);

        $this->review([$this->files[0]->id => ['approve' => 0, 'note' => 'Too late']])->assertSessionHasErrors('review');
    }

    public function test_another_recruiter_cannot_review_and_unsent_files_are_hidden(): void
    {
        $stranger = User::factory()->recruiter()->create();
        $this->send();

        $this->review([$this->files[0]->id => ['approve' => 1]], $stranger)->assertNotFound();
        $this->actingAs($stranger)->get(route('project-documents.download', $this->files[0]))->assertNotFound();
        $this->actingAs($this->recruiter)->get(route('project-documents.download', $this->files[0]))->assertOk();
    }

    public function test_staff_without_a_whatsapp_number_only_get_mail_and_in_app(): void
    {
        $noPhone = User::factory()->admin()->create(['whatsapp_number' => null]);
        $this->send();

        $this->review([$this->files[0]->id => ['approve' => 0, 'note' => 'Bad scan']]);

        Notification::assertSentTo($noPhone, ProjectFilesRejected::class, fn ($n, array $channels): bool => ! in_array(WhatsAppChannel::class, $channels, true));
    }

    public function test_malformed_or_foreign_decisions_are_rejected_without_server_errors(): void
    {
        $this->send();
        $url = route('recruiter.projects.candidates.review', [$this->project, $this->student]);

        $this->actingAs($this->recruiter)->post($url, ['decisions' => 'nope'])->assertSessionHasErrors('decisions');
        $this->actingAs($this->recruiter)->post($url, ['decisions' => [$this->files[0]->id => 'x']])->assertSessionHasErrors();

        $otherEntry = RecruiterProjectCandidate::create(['recruiter_project_id' => RecruiterProject::factory()->create()->id, 'student_profile_id' => StudentProfile::factory()->enrolled()->create()->id]);
        $foreignFile = $otherEntry->requirements()->create(['title' => 'x', 'file_path' => 'p', 'original_name' => 'p']);

        $this->review([$foreignFile->id => ['approve' => 1]])->assertSessionHasErrors('review');
        $this->assertSame(RequirementReviewStatus::Pending, $foreignFile->fresh()->review_status);

        $this->review([$this->files[0]->id => ['approve' => 1, 'sample' => UploadedFile::fake()->create('evil.php', 5)]])
            ->assertSessionHasErrors("decisions.{$this->files[0]->id}.sample");
    }
}
