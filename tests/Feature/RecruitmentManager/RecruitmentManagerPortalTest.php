<?php

namespace Tests\Feature\RecruitmentManager;

use App\Enums\PlacementStatus;
use App\Enums\RegistrationPurpose;
use App\Enums\RequirementReviewStatus;
use App\Enums\UserRole;
use App\Filament\Resources\RecruitmentManagers\Pages\CreateRecruitmentManager;
use App\Filament\Resources\Users\Concerns\HandlesUserMutation;
use App\Models\ProjectRequirement;
use App\Models\RecruiterProject;
use App\Models\RecruiterProjectCandidate;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\PlacementAccessRequested;
use App\Notifications\ProjectFilesResubmitted;
use App\Notifications\ProjectFilesSentToRecruiter;
use App\Notifications\StudentApproved;
use App\Notifications\StudentShortlisted;
use App\Notifications\WelcomeWithCredentials;
use App\Services\ProjectApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

class RecruitmentManagerPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(ProjectRequirement::DISK);
        $this->manager = User::factory()->recruitmentManager()->create(['email_verified_at' => now()]);
    }

    /**
     * @return array{RecruiterProject, RecruiterProjectCandidate, StudentProfile}
     */
    private function projectWithFiles(bool $sent = false): array
    {
        $recruiter = User::factory()->recruiter()->create();
        $project = RecruiterProject::factory()->create(['user_id' => $recruiter->id, 'finalized_at' => now()]);
        $student = StudentProfile::factory()->enrolled()->forPlacement()->create();
        $entry = RecruiterProjectCandidate::create(['recruiter_project_id' => $project->id, 'student_profile_id' => $student->id]);
        $entry->requirements()->createMany([['title' => 'Passport'], ['title' => 'Degree']]);

        if ($sent) {
            foreach ($entry->requirements as $requirement) {
                $requirement->update(['file_path' => "project-documents/x/{$requirement->id}.pdf", 'original_name' => 'x.pdf']);
                Storage::disk(ProjectRequirement::DISK)->put($requirement->file_path, 'pdf');
            }
            app(ProjectApprovalService::class)->sendToRecruiter($entry->fresh());
        }

        return [$project, $entry->fresh(), $student];
    }

    public function test_dashboard_entry_redirects_a_signed_in_manager_to_their_dashboard(): void
    {
        $this->actingAs($this->manager)->get(route('dashboard'))->assertRedirect(route('recruitment-manager.dashboard'));
    }

    public function test_other_roles_cannot_open_the_manager_portal_and_the_manager_cannot_open_theirs(): void
    {
        [$project] = $this->projectWithFiles();
        $routes = [
            route('recruitment-manager.dashboard'),
            route('recruitment-manager.candidates.index'),
            route('recruitment-manager.projects.index'),
            route('recruitment-manager.projects.show', $project),
            route('recruitment-manager.recruiters.index'),
        ];

        foreach ([User::factory()->recruiter()->create(), User::factory()->trainingManager()->create(), User::factory()->student()->create(), User::factory()->teacher()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user);
            foreach ($routes as $url) {
                $this->get($url)->assertForbidden();
            }
        }

        $this->actingAs($this->manager)->get(route('recruiter.projects.index'))->assertForbidden();
        $this->get('/bv-admin')->assertForbidden();
    }

    public function test_dashboard_shows_placement_counts_and_the_readiness_heatmap(): void
    {
        StudentProfile::factory()->enrolled()->forPlacement()->count(2)->create();
        StudentProfile::factory()->enrolled()->forPlacement()->hired()->create();
        StudentProfile::factory()->create();
        StudentProfile::factory()->create(['placement_requested_at' => now()]);

        $this->actingAs($this->manager)->get(route('recruitment-manager.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/recruitment-manager/dashboard')
                ->where('counts', ['onboarded' => 3, 'placed' => 1, 'pending' => 2, 'requests' => 1])
                ->has('heatmap.rows', 3)
                ->has('heatmap.rows.0', fn (Assert $row) => $row
                    ->hasAll(['id', 'name', 'student_number', 'batch', 'placement_status', 'profile', 'assignments', 'submission_rate', 'attendance'])
                    ->etc()));
    }

    public function test_manager_switches_purpose_and_placement_status_manually(): void
    {
        $profile = StudentProfile::factory()->create(['placement_requested_at' => now()]);

        $this->actingAs($this->manager)->put(route('recruitment-manager.candidates.update', $profile), ['registration_purpose' => 'training_and_placement'])->assertRedirect();
        $profile->refresh();
        $this->assertSame(RegistrationPurpose::TrainingAndPlacement, $profile->registration_purpose);
        $this->assertNull($profile->placement_requested_at);

        $this->put(route('recruitment-manager.candidates.update', $profile), ['placement_status' => 'hired'])->assertRedirect();
        $this->assertSame(PlacementStatus::Hired, $profile->fresh()->placement_status);

        $this->put(route('recruitment-manager.candidates.update', $profile), ['registration_purpose' => 'bogus'])->assertSessionHasErrors('registration_purpose');
        $this->put(route('recruitment-manager.candidates.update', $profile), ['placement_status' => 'bogus'])->assertSessionHasErrors('placement_status');

        $this->actingAs(User::factory()->recruiter()->create())
            ->put(route('recruitment-manager.candidates.update', $profile), ['placement_status' => 'open'])->assertForbidden();
    }

    public function test_candidates_list_filters_and_puts_requests_first(): void
    {
        $plain = StudentProfile::factory()->create();
        $requested = StudentProfile::factory()->create(['placement_requested_at' => now()]);
        $placement = StudentProfile::factory()->forPlacement()->create();

        $this->actingAs($this->manager)->get(route('recruitment-manager.candidates.index'))
            ->assertInertia(fn (Assert $page) => $page->has('profiles.data', 3)->where('profiles.data.0.id', $requested->id));

        $this->get(route('recruitment-manager.candidates.index', ['purpose' => 'training_and_placement']))
            ->assertInertia(fn (Assert $page) => $page->has('profiles.data', 1)->where('profiles.data.0.id', $placement->id));

        $this->get(route('recruitment-manager.candidates.index', ['requested' => 1]))
            ->assertInertia(fn (Assert $page) => $page->has('profiles.data', 1)->where('profiles.data.0.id', $requested->id));
        $this->assertNotNull($plain);
    }

    public function test_candidates_requested_filter_accepts_the_booleans_the_browser_sends(): void
    {
        $requested = StudentProfile::factory()->create(['placement_requested_at' => now()]);
        StudentProfile::factory()->create();

        $this->actingAs($this->manager)->get(route('recruitment-manager.candidates.index', ['requested' => 'true']))
            ->assertSessionHasNoErrors()
            ->assertInertia(fn (Assert $page) => $page->has('profiles.data', 1)->where('profiles.data.0.id', $requested->id));

        $this->get(route('recruitment-manager.candidates.index', ['requested' => 'false']))
            ->assertSessionHasNoErrors()
            ->assertInertia(fn (Assert $page) => $page->has('profiles.data', 2));
    }

    public function test_manager_can_open_any_candidate_profile_and_project_documents(): void
    {
        [, $entry, $student] = $this->projectWithFiles(true);
        $training = StudentProfile::factory()->enrolled()->create();

        $this->actingAs($this->manager)->get(route('recruiter.students.show', $training))->assertOk();
        $this->get(route('recruiter.students.show', $student))->assertOk();
        $this->get(route('project-documents.download', $entry->requirements()->first()))->assertOk();
    }

    public function test_project_list_shows_only_submitted_projects_and_detail_has_files_and_notes(): void
    {
        [$project] = $this->projectWithFiles(true);
        RecruiterProject::factory()->create(['finalized_at' => null]);

        $this->actingAs($this->manager)->get(route('recruitment-manager.projects.index'))
            ->assertInertia(fn (Assert $page) => $page->has('projects', 1)->where('projects.0.id', $project->id));

        $this->get(route('recruitment-manager.projects.show', $project))
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/recruitment-manager/projects/show')
                ->where('entries.0.status', 'sent')
                ->has('entries.0.requirements', 2));

        $this->get(route('recruitment-manager.projects.show', RecruiterProject::factory()->create(['finalized_at' => null])))->assertNotFound();
    }

    public function test_manager_uploads_files_sends_to_recruiter_and_answers_rejections(): void
    {
        Notification::fake();
        [$project, $entry, $student] = $this->projectWithFiles();
        [$first, $second] = $entry->requirements;

        $this->actingAs($this->manager)->post(route('recruitment-manager.projects.candidates.send', [$project, $student]))
            ->assertSessionHasErrors('send');

        foreach ([$first, $second] as $requirement) {
            $this->post(route('recruitment-manager.projects.requirements.upload', [$project, $requirement]), [
                'file' => UploadedFile::fake()->create("{$requirement->title}.pdf", 20, 'application/pdf'),
            ])->assertSessionHasNoErrors();
            Storage::disk(ProjectRequirement::DISK)->assertExists($requirement->fresh()->file_path);
        }

        $this->post(route('recruitment-manager.projects.candidates.send', [$project, $student]))->assertSessionHasNoErrors();
        Notification::assertSentTo($project->owner, ProjectFilesSentToRecruiter::class);

        $second->update(['review_status' => RequirementReviewStatus::Rejected, 'rejection_note' => 'Blurry']);
        $this->post(route('recruitment-manager.projects.requirements.upload', [$project, $second]), [
            'file' => UploadedFile::fake()->create('new.pdf', 20, 'application/pdf'),
        ]);

        $this->assertSame(RequirementReviewStatus::Pending, $second->fresh()->review_status);
        Notification::assertSentToTimes($project->owner, ProjectFilesResubmitted::class, 1);
    }

    public function test_upload_is_validated_scoped_to_the_project_and_blocked_after_approval(): void
    {
        [$project, $entry] = $this->projectWithFiles();
        [$otherProject, $otherEntry] = $this->projectWithFiles();
        $requirement = $entry->requirements()->first();
        $url = route('recruitment-manager.projects.requirements.upload', [$project, $requirement]);

        $this->actingAs($this->manager)->post($url, ['file' => UploadedFile::fake()->create('evil.php', 5)])->assertSessionHasErrors('file');
        $this->post($url, [])->assertSessionHasErrors('file');
        $this->post(route('recruitment-manager.projects.requirements.upload', [$otherProject, $requirement]), [
            'file' => UploadedFile::fake()->create('a.pdf', 5, 'application/pdf'),
        ])->assertNotFound();

        $otherEntry->update(['approved_at' => now()]);
        $this->post(route('recruitment-manager.projects.requirements.upload', [$otherProject, $otherEntry->requirements()->first()]), [
            'file' => UploadedFile::fake()->create('a.pdf', 5, 'application/pdf'),
        ])->assertStatus(422);
    }

    public function test_recruiters_list_includes_every_project_of_each_recruiter(): void
    {
        [$project] = $this->projectWithFiles();
        $draft = RecruiterProject::factory()->create(['user_id' => $project->user_id, 'finalized_at' => null]);

        $this->actingAs($this->manager)->get(route('recruitment-manager.recruiters.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('recruiters.0.projects_count', 2)
                ->has('recruiters.0.projects', 2)
                ->where('recruiters.0.projects.0.id', $draft->id)
                ->where('recruiters.0.projects.0.finalized', false)
                ->where('recruiters.0.projects.1.students_count', 1));
    }

    public function test_manager_messages_students_of_a_project(): void
    {
        Notification::fake();
        [$project, $entry, $student] = $this->projectWithFiles();

        $this->actingAs($this->manager)->post(route('recruitment-manager.projects.message', $project), ['type' => 'congratulate', 'message' => 'Well done'])->assertRedirect();
        Notification::assertSentTo($student->user, StudentShortlisted::class);
        $this->assertNotNull($entry->fresh()->congratulated_at);

        $this->post(route('recruitment-manager.projects.message', $project), ['type' => 'nope', 'message' => ''])->assertSessionHasErrors(['type', 'message']);
    }

    public function test_manager_creates_and_edits_recruiters_but_not_other_roles(): void
    {
        Notification::fake();

        $this->actingAs($this->manager)->post(route('recruitment-manager.recruiters.store'), [
            'name' => 'Rita Rao', 'email' => 'rita@example.com', 'password' => 'Str0ng-Passw0rd!', 'whatsapp_number' => '+91 98000 00012',
        ])->assertRedirect(route('recruitment-manager.recruiters.index'));

        $recruiter = User::query()->where('email', 'rita@example.com')->firstOrFail();
        $this->assertSame(UserRole::Recruiter, $recruiter->role);
        $this->assertNotNull($recruiter->email_verified_at);
        $this->assertSame('+91 98000 00012', $recruiter->whatsapp_number);
        Notification::assertSentTo($recruiter, WelcomeWithCredentials::class);

        $this->post(route('recruitment-manager.recruiters.store'), ['name' => 'Dup', 'email' => 'rita@example.com', 'password' => 'Str0ng-Passw0rd!'])->assertSessionHasErrors('email');
        $this->post(route('recruitment-manager.recruiters.store'), ['name' => 'Bad', 'email' => 'bad@example.com', 'password' => 'Str0ng-Passw0rd!', 'whatsapp_number' => '<script>'])->assertSessionHasErrors('whatsapp_number');

        $this->put(route('recruitment-manager.recruiters.update', $recruiter), ['name' => 'Rita R', 'email' => 'rita@example.com', 'whatsapp_number' => ''])->assertRedirect();
        $this->assertSame('Rita R', $recruiter->fresh()->name);
        $this->assertNull($recruiter->fresh()->whatsapp_number);

        $teacher = User::factory()->teacher()->create();
        $this->get(route('recruitment-manager.recruiters.edit', $teacher))->assertNotFound();
        $this->put(route('recruitment-manager.recruiters.update', $teacher), ['name' => 'X', 'email' => 'x@example.com'])->assertNotFound();
    }

    public function test_managers_receive_placement_notifications_with_links_into_their_dashboard(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        [$project, $entry, $student] = $this->projectWithFiles(true);

        app(ProjectApprovalService::class)->approveAll($entry->fresh(), $project->owner);
        Notification::assertSentTo($this->manager, StudentApproved::class, fn ($n) => str_contains($n->toArray($this->manager)['url'], '/recruitment-manager/projects/'.$project->id));
        Notification::assertSentTo($admin, StudentApproved::class, fn ($n) => str_contains($n->toArray($admin)['url'], '/bv-admin/recruiter-projects/'));

        $training = StudentProfile::factory()->create();
        $this->actingAs($training->user)->post(route('student.placement-request.store'));
        Notification::assertSentTo($this->manager, PlacementAccessRequested::class, fn ($n) => str_ends_with($n->toArray($this->manager)['url'], '/recruitment-manager/candidates'));
        $this->assertNotNull($student);
    }

    public function test_manager_can_read_their_notifications_and_admin_creates_managers_in_filament(): void
    {
        $this->actingAs($this->manager)->get(route('student.notifications.index'))->assertOk();

        $admin = User::factory()->admin()->create();
        Livewire::actingAs($admin)->test(CreateRecruitmentManager::class)
            ->fillForm(['name' => 'Mona Manager', 'email' => 'mona@example.com', 'password' => 'Str0ng-Passw0rd!', 'whatsapp_number' => '+919800000099'])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::query()->where('email', 'mona@example.com')->firstOrFail();
        $this->assertSame(UserRole::RecruitmentManager, $created->role);
        $this->assertSame('+919800000099', $created->whatsapp_number);
        $this->assertTrue($created->whatsAppNumber() === '+919800000099');
    }

    public function test_every_manager_endpoint_rejects_guests_and_other_roles(): void
    {
        [$project, $entry, $student] = $this->projectWithFiles();
        $requirement = $entry->requirements()->first();
        $recruiter = User::factory()->recruiter()->create();

        $endpoints = [
            ['put', route('recruitment-manager.candidates.update', $student), ['placement_status' => 'hired']],
            ['post', route('recruitment-manager.projects.requirements.upload', [$project, $requirement]), []],
            ['post', route('recruitment-manager.projects.candidates.send', [$project, $student]), []],
            ['post', route('recruitment-manager.projects.message', $project), ['type' => 'congratulate', 'message' => 'x']],
            ['post', route('recruitment-manager.recruiters.store'), ['name' => 'A', 'email' => 'a@example.com', 'password' => 'Str0ng-Passw0rd!']],
            ['put', route('recruitment-manager.recruiters.update', $recruiter), ['name' => 'A', 'email' => 'a@example.com']],
        ];

        foreach ($endpoints as [$method, $url, $data]) {
            $this->post(route('logout'));
            $this->{$method}($url, $data)->assertRedirect(route('login'));

            foreach ([User::factory()->recruiter()->create(), User::factory()->admin()->create(), User::factory()->trainingManager()->create(), User::factory()->employee()->create(), User::factory()->student()->create(), User::factory()->teacher()->create()] as $user) {
                $this->actingAs($user)->{$method}($url, $data)->assertForbidden();
            }
        }

        $this->assertSame(PlacementStatus::Open, $student->fresh()->placement_status);
        $this->assertSame(0, User::query()->where('email', 'a@example.com')->count());
    }

    public function test_manager_cannot_escalate_roles_or_touch_non_recruiter_accounts(): void
    {
        Notification::fake();
        $this->actingAs($this->manager);

        $this->post(route('recruitment-manager.recruiters.store'), [
            'name' => 'Sneaky', 'email' => 'sneaky@example.com', 'password' => 'Str0ng-Passw0rd!', 'role' => 'admin',
        ])->assertRedirect();
        $this->assertSame(UserRole::Recruiter, User::query()->where('email', 'sneaky@example.com')->firstOrFail()->role);

        $recruiter = User::factory()->recruiter()->create();
        $this->put(route('recruitment-manager.recruiters.update', $recruiter), [
            'name' => 'Renamed', 'email' => $recruiter->email, 'role' => 'admin', 'registration_paid_at' => null,
        ])->assertRedirect();
        $this->assertSame(UserRole::Recruiter, $recruiter->fresh()->role);

        foreach ([User::factory()->admin()->create(), User::factory()->employee()->create(), User::factory()->trainingManager()->create(), User::factory()->student()->create(), $this->manager] as $other) {
            $this->get(route('recruitment-manager.recruiters.edit', $other))->assertNotFound();
            $this->put(route('recruitment-manager.recruiters.update', $other), ['name' => 'Taken', 'email' => 'taken@example.com', 'password' => 'Str0ng-Passw0rd!'])->assertNotFound();
        }

        $this->assertSame(0, User::query()->where('email', 'taken@example.com')->count());
        $this->put(route('recruitment-manager.recruiters.update', $recruiter), ['name' => 'R', 'email' => $this->manager->email])->assertSessionHasErrors('email');
        $this->put(route('recruitment-manager.recruiters.update', $recruiter), ['name' => 'R', 'email' => $recruiter->email, 'password' => 'short'])->assertSessionHasErrors('password');
    }

    public function test_manager_actions_are_scoped_to_the_given_project(): void
    {
        Notification::fake();
        [$project, , $student] = $this->projectWithFiles();
        [$other, $otherEntry, $otherStudent] = $this->projectWithFiles();

        $this->actingAs($this->manager)->post(route('recruitment-manager.projects.candidates.send', [$project, $otherStudent]))->assertNotFound();

        $this->post(route('recruitment-manager.projects.message', $project), ['type' => 'congratulate', 'message' => 'Hi', 'student_profile_id' => $otherStudent->id])->assertRedirect();
        Notification::assertNotSentTo($otherStudent->user, StudentShortlisted::class);
        $this->assertNull($otherEntry->fresh()->congratulated_at);
        $this->assertNotNull($student);
        $this->assertNotNull($other);
    }

    public function test_blank_whatsapp_field_missing_from_a_form_does_not_wipe_the_saved_number(): void
    {
        $recruiter = User::factory()->recruiter()->create(['whatsapp_number' => '+919800000055']);
        $page = new class
        {
            use HandlesUserMutation {
                updateUserIdentity as public;
            }
        };

        $page->updateUserIdentity($recruiter, ['name' => 'New', 'email' => $recruiter->email]);

        $this->assertSame('+919800000055', $recruiter->fresh()->whatsapp_number);
    }

    public function test_sending_files_confirms_to_the_manager_and_shows_up_in_the_recruiters_notifications(): void
    {
        [$project, $entry, $student] = $this->projectWithFiles();
        foreach ($entry->requirements as $requirement) {
            $requirement->update(['file_path' => "project-documents/x/{$requirement->id}.pdf", 'original_name' => 'x.pdf']);
        }

        $this->actingAs($this->manager)->post(route('recruitment-manager.projects.candidates.send', [$project, $student]))
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, $project->owner->name));

        $this->actingAs($project->owner)->get(route('student.notifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/student/notifications')
                ->where('unread_count', 1)
                ->where('notifications.0.title', 'Approved by BV: please review')
                ->where('notifications.0.url', '/recruiter/projects/'.$project->id));
    }

    public function test_recruiters_employees_and_admins_can_read_notifications_but_other_roles_cannot(): void
    {
        foreach ([User::factory()->recruiter()->create(), User::factory()->employee()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->get(route('student.notifications.index'))->assertOk();
            $this->post(route('student.notifications.read-all'))->assertRedirect();
        }

        foreach ([User::factory()->teacher()->create(), User::factory()->trainingManager()->create()] as $user) {
            $this->actingAs($user)->get(route('student.notifications.index'))->assertForbidden();
        }

        $this->actingAs(User::factory()->recruiter()->create())->post(route('student.placement-request.store'))->assertForbidden();
    }
}
