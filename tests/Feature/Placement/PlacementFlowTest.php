<?php

namespace Tests\Feature\Placement;

use App\Enums\RequirementReviewStatus;
use App\Filament\Resources\RecruiterProjects\Pages\ViewRecruiterProject;
use App\Filament\Resources\RecruiterProjects\RelationManagers\StudentsRelationManager;
use App\Models\ProjectRequirement;
use App\Models\RecruiterProject;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\ProjectFilesRejected;
use App\Notifications\ProjectFilesResubmitted;
use App\Notifications\ProjectFilesSentToRecruiter;
use App\Notifications\StudentApproved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PlacementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_placement_flow_from_shortlist_to_recruiter_acceptance(): void
    {
        Storage::fake(ProjectRequirement::DISK);
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $recruiter = User::factory()->recruiter()->create();
        $student = StudentProfile::factory()->enrolled()->forPlacement()->create();
        $training = StudentProfile::factory()->enrolled()->create();

        // recruiter shortlists (training-only student is invisible) and finalizes
        $this->actingAs($recruiter)->post(route('recruiter.projects.store'), [
            'name' => 'Nurses for Germany', 'country' => 'Germany', 'sector' => 'Nursing', 'timeline' => '3 months',
        ])->assertRedirect();
        $project = RecruiterProject::query()->firstOrFail();

        $this->post(route('recruiter.projects.candidates.store', [$project, $training]))->assertNotFound();
        $this->post(route('recruiter.projects.candidates.store', [$project, $student]))->assertRedirect();
        $this->post(route('recruiter.projects.finalize', $project), ['requirements' => ['Passport', 'Degree']])->assertRedirect();

        $entry = $project->entries()->firstOrFail();
        $requirements = $entry->requirements()->get();

        // recruiter cannot review before BV sends
        $this->post(route('recruiter.projects.candidates.approve-all', [$project, $student]))->assertSessionHasErrors('review');

        // admin uploads every file and sends
        $upload = function (ProjectRequirement $requirement, string $suffix) use ($project, $entry): string {
            $path = "project-documents/{$project->id}/{$entry->id}/{$requirement->id}-{$suffix}.pdf";
            Storage::disk(ProjectRequirement::DISK)->put($path, 'pdf');

            return $path;
        };
        $component = Livewire::actingAs($admin)->test(StudentsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewRecruiterProject::class]);
        $component->mountTableAction('documents', $entry)
            ->setTableActionData(['requirements' => $requirements->map(fn (ProjectRequirement $r): array => [
                'id' => $r->id, 'title' => $r->title, 'original_name' => $r->title.'.pdf', 'file' => [(string) Str::uuid() => $upload($r, 'v1')],
            ])->all()])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();
        $component->callTableAction('sendToRecruiter', $entry);
        Notification::assertSentTo($recruiter, ProjectFilesSentToRecruiter::class);

        // recruiter approves one file, rejects the other with a note and a sample
        $this->actingAs($recruiter)->post(route('recruiter.projects.candidates.review', [$project, $student]), ['decisions' => [
            $requirements[0]->id => ['approve' => 1],
            $requirements[1]->id => ['approve' => 0, 'note' => 'Wrong name spelling', 'sample' => UploadedFile::fake()->create('sample.pdf', 10, 'application/pdf')],
        ]])->assertSessionHasNoErrors();
        Notification::assertSentTo($admin, ProjectFilesRejected::class);
        $this->assertFalse($entry->fresh()->isApproved());

        // admin sees the rejection and replaces the rejected file
        $rejected = $requirements[1]->fresh();
        $this->assertSame(RequirementReviewStatus::Rejected, $rejected->review_status);
        $component = Livewire::actingAs($admin)->test(StudentsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewRecruiterProject::class]);
        $component->mountTableAction('documents', $entry)
            ->setTableActionData(['requirements' => $requirements->map(fn (ProjectRequirement $r): array => [
                'id' => $r->id,
                'title' => $r->title,
                'original_name' => $r->title.'.pdf',
                'file' => [(string) Str::uuid() => $r->is($requirements[1]) ? $upload($r, 'v2') : $r->fresh()->file_path],
            ])->all()])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();
        Notification::assertSentTo($recruiter, ProjectFilesResubmitted::class);
        $this->assertSame(RequirementReviewStatus::Pending, $requirements[1]->fresh()->review_status);
        $this->assertSame(RequirementReviewStatus::Approved, $requirements[0]->fresh()->review_status);

        // recruiter approves the rest: accepted, admins told, placement status untouched
        $this->actingAs($recruiter)->post(route('recruiter.projects.candidates.review', [$project, $student]), ['decisions' => [
            $requirements[1]->id => ['approve' => 1],
        ]])->assertSessionHasNoErrors();

        $this->assertTrue($entry->fresh()->isApproved());
        $this->assertSame('approved', $project->fresh()->stage());
        $this->assertFalse($student->fresh()->isHired());
        Notification::assertSentTo($admin, StudentApproved::class);
    }

    public function test_students_cannot_change_their_own_purpose_or_placement_status(): void
    {
        $profile = StudentProfile::factory()->create();
        $this->actingAs($profile->user);

        $tamper = ['registration_purpose' => 'training_and_placement', 'placement_status' => 'hired', 'placement_requested_at' => now()->toDateTimeString()];

        $this->post(route('student.cv.update'), ['first_name' => 'A', 'last_name' => 'B', 'vitae' => ['is_newbie' => true], ...$tamper]);
        $this->patch(route('profile.update'), ['name' => 'New Name', 'email' => $profile->user->email, ...$tamper]);

        $fresh = $profile->fresh();
        $this->assertFalse($fresh->wantsPlacement());
        $this->assertFalse($fresh->isHired());
        $this->assertNull($fresh->placement_requested_at);
    }

    public function test_review_endpoints_are_closed_to_students_and_guests(): void
    {
        $recruiter = User::factory()->recruiter()->create();
        $project = RecruiterProject::factory()->create(['user_id' => $recruiter->id]);
        $student = StudentProfile::factory()->enrolled()->forPlacement()->create();
        $review = route('recruiter.projects.candidates.review', [$project, $student]);
        $approve = route('recruiter.projects.candidates.approve-all', [$project, $student]);

        $this->post($review)->assertRedirect(route('login'));
        $this->post($approve)->assertRedirect(route('login'));

        $this->actingAs($student->user)->post($review)->assertForbidden();
        $this->post($approve)->assertForbidden();
        $this->post(route('student.placement-request.store'))->assertStatus(422);
    }
}
