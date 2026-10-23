<?php

namespace Tests\Feature\Recruiter;

use App\Filament\Resources\RecruiterProjects\Pages\ViewRecruiterProject;
use App\Filament\Resources\RecruiterProjects\RelationManagers\StudentsRelationManager;
use App\Models\RecruiterProject;
use App\Models\RecruiterProjectCandidate;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\StudentRequirementsRequested;
use App\Notifications\StudentShortlisted;
use App\Support\StudentNotificationPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectStudentMessagingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private RecruiterProject $project;

    private RecruiterProjectCandidate $withPhone;

    private RecruiterProjectCandidate $withoutPhone;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp.enabled' => true]);

        $this->admin = User::factory()->admin()->create();
        $this->project = RecruiterProject::factory()->create(['finalized_at' => now(), 'name' => 'Nurses for Germany']);

        $a = StudentProfile::factory()->enrolled()->create(['mobile_whatsapp' => '+919800000001']);
        $b = StudentProfile::factory()->enrolled()->create(['mobile_whatsapp' => null]);
        $this->withPhone = RecruiterProjectCandidate::create(['recruiter_project_id' => $this->project->id, 'student_profile_id' => $a->id]);
        $this->withoutPhone = RecruiterProjectCandidate::create(['recruiter_project_id' => $this->project->id, 'student_profile_id' => $b->id]);
        $this->withPhone->requirements()->createMany([['title' => 'Passport copy'], ['title' => 'Degree']]);
        $this->withoutPhone->requirements()->create(['title' => 'Passport copy']);
    }

    private function headerAction(string $action, array $data = []): void
    {
        Livewire::actingAs($this->admin)
            ->test(ViewRecruiterProject::class, ['record' => $this->project->getKey()])
            ->callAction($action, $data);
    }

    public function test_congratulation_goes_to_every_student_with_channels_and_is_tracked(): void
    {
        Notification::fake();

        $this->headerAction('congratulateAll', ['message' => 'You are shortlisted! We will call you soon.']);

        Notification::assertSentTo($this->withPhone->studentProfile->user, StudentShortlisted::class, function (StudentShortlisted $n, array $channels): bool {
            return in_array('database', $channels, true) && in_array('mail', $channels, true) && in_array(WhatsAppChannel::class, $channels, true);
        });
        Notification::assertSentTo($this->withoutPhone->studentProfile->user, StudentShortlisted::class, fn ($n, array $channels): bool => ! in_array(WhatsAppChannel::class, $channels, true));
        $this->assertNotNull($this->withPhone->fresh()->congratulated_at);
        $this->assertNull($this->withPhone->fresh()->requirements_sent_at);
    }

    public function test_requirements_message_lists_each_students_own_documents(): void
    {
        $this->headerAction('sendRequirements', ['message' => 'Please get these ready:']);

        $notification = $this->withPhone->studentProfile->user->notifications()->latest()->first();
        $this->assertSame('requirements', $notification->data['kind']);
        $this->assertSame(['Passport copy', 'Degree'], $notification->data['requirements']);
        $this->assertStringContainsString("• Passport copy\n• Degree", $notification->data['body']);

        $other = $this->withoutPhone->studentProfile->user->notifications()->latest()->first();
        $this->assertSame(['Passport copy'], $other->data['requirements']);
        $this->assertNotNull($this->withPhone->fresh()->requirements_sent_at);
    }

    public function test_students_hired_elsewhere_and_students_without_requirements_are_skipped(): void
    {
        Notification::fake();
        $this->withPhone->studentProfile->update(['placement_status' => 'hired']);
        $this->withoutPhone->requirements()->delete();

        $this->headerAction('congratulateAll', ['message' => 'Hello']);
        Notification::assertNotSentTo($this->withPhone->studentProfile->user, StudentShortlisted::class);
        Notification::assertSentTo($this->withoutPhone->studentProfile->user, StudentShortlisted::class);

        $this->headerAction('sendRequirements', ['message' => 'Docs']);
        Notification::assertNotSentTo($this->withoutPhone->studentProfile->user, StudentRequirementsRequested::class);
    }

    public function test_row_action_messages_one_student_and_resend_is_allowed(): void
    {
        Notification::fake();

        $component = Livewire::actingAs($this->admin)
            ->test(StudentsRelationManager::class, ['ownerRecord' => $this->project, 'pageClass' => ViewRecruiterProject::class]);

        $component->callTableAction('congratulate', $this->withPhone, ['message' => 'Hi']);
        $component->callTableAction('congratulate', $this->withPhone, ['message' => 'Hi again']);

        Notification::assertSentToTimes($this->withPhone->studentProfile->user, StudentShortlisted::class, 2);
        Notification::assertNotSentTo($this->withoutPhone->studentProfile->user, StudentShortlisted::class);
    }

    public function test_presenter_exposes_the_notification_kind_and_whatsapp_text_is_single_line(): void
    {
        $this->headerAction('sendRequirements', ['message' => 'Please get these ready:']);
        $user = $this->withPhone->studentProfile->user;
        $stored = $user->notifications()->latest()->first();

        $this->assertSame('requirements', StudentNotificationPresenter::format($stored)['kind']);
        $this->assertSame('info', StudentNotificationPresenter::format(new DatabaseNotification(['data' => ['title' => 'x']]))['kind']);

        config(['services.whatsapp.twilio.templates.update' => 'HX123']);
        $content = (new StudentRequirementsRequested($this->withPhone->fresh(), 'Please get these ready:'))->toWhatsAppContent($user);
        $this->assertStringNotContainsString("\n", $content->variables['2']);
        $this->assertStringContainsString('• Passport copy • Degree', $content->variables['2']);
    }

    public function test_student_notifications_page_receives_the_kind(): void
    {
        $this->headerAction('congratulateAll', ['message' => 'Shortlisted!']);
        $student = $this->withPhone->studentProfile->user;

        $this->actingAs($student)->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('notifications.0.kind', 'celebration'));
    }
}
