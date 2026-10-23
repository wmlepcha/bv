<?php

namespace Tests\Feature\Student;

use App\Enums\RegistrationPurpose;
use App\Models\RecruiterProject;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\PlacementAccessRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PlacementPurposeTest extends TestCase
{
    use RefreshDatabase;

    public function test_recruiters_only_see_students_registered_for_placement(): void
    {
        $placement = StudentProfile::factory()->enrolled()->forPlacement()->create();
        $training = StudentProfile::factory()->enrolled()->create();
        $recruiter = User::factory()->recruiter()->create();

        $this->actingAs($recruiter)->get(route('recruiter.students.index'))
            ->assertInertia(fn (Assert $page) => $page->has('profiles.data', 1)->where('profiles.data.0.id', $placement->id));

        $this->get(route('recruiter.students.show', $training))->assertNotFound();
        $this->post(route('recruiter.students.cv.translate', $training), ['locale' => 'de'])->assertNotFound();

        $project = RecruiterProject::factory()->create(['user_id' => $recruiter->id]);
        $this->post(route('recruiter.projects.candidates.store', [$project, $training]))->assertNotFound();
        $this->post(route('recruiter.projects.candidates.store', [$project, $placement]))->assertRedirect();
    }

    public function test_admin_and_teacher_views_are_not_restricted_by_purpose(): void
    {
        $training = StudentProfile::factory()->enrolled()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('recruiter.students.index'))
            ->assertInertia(fn (Assert $page) => $page->has('profiles.data', 1));
        $this->get(route('recruiter.students.show', $training))->assertOk();
    }

    public function test_training_student_can_request_placement_once_and_staff_are_notified(): void
    {
        Notification::fake();
        config(['services.whatsapp.enabled' => true]);
        $admin = User::factory()->admin()->create(['whatsapp_number' => '+919800000011']);
        $profile = StudentProfile::factory()->create();

        $this->actingAs($profile->user)->post(route('student.placement-request.store'))->assertRedirect();
        $this->post(route('student.placement-request.store'))->assertRedirect();

        $this->assertNotNull($profile->fresh()->placement_requested_at);
        Notification::assertSentToTimes($admin, PlacementAccessRequested::class, 1);
        Notification::assertSentTo($admin, PlacementAccessRequested::class, fn ($n, array $channels): bool => in_array(WhatsAppChannel::class, $channels, true));

        $this->get(route('profile.edit'))->assertInertia(fn (Assert $page) => $page
            ->where('placement.wants_placement', false)
            ->where('placement.requested', true));
    }

    public function test_placement_students_cannot_request_and_switching_purpose_clears_the_request(): void
    {
        $registered = StudentProfile::factory()->forPlacement()->create();
        $this->actingAs($registered->user)->post(route('student.placement-request.store'))->assertStatus(422);

        $profile = StudentProfile::factory()->create(['placement_requested_at' => now()]);
        $profile->update(['registration_purpose' => RegistrationPurpose::TrainingAndPlacement]);

        $this->assertNull($profile->fresh()->placement_requested_at);
        $this->assertTrue($profile->fresh()->wantsPlacement());
    }

    public function test_non_students_cannot_use_the_request_endpoint(): void
    {
        $this->actingAs(User::factory()->recruiter()->create())
            ->post(route('student.placement-request.store'))
            ->assertForbidden();
    }
}
