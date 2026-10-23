<?php

namespace Tests\Feature\Auth;

use App\Enums\RegistrationPurpose;
use App\Enums\SpecialId;
use App\Models\User;
use App\Notifications\StudentRegistered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class StudentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'first_name' => 'Student',
            'last_name' => 'User',
            'email' => 'student@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'state' => 'Sikkim',
            'special_id' => 'COI',
            'registration_purpose' => 'training_and_placement',
            'form_token' => Crypt::encryptString((string) (now()->timestamp - 10)),
            ...$overrides,
        ];
    }

    public function test_student_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('student.register.create'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('auth/register')
            ->has('states')
            ->has('specialIds')
            ->has('purposes', 2)
            ->has('formToken'));
    }

    public function test_students_can_register(): void
    {
        Notification::fake();

        $response = $this->post(route('student.register.store'), $this->validPayload());

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', 'student@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
        Notification::assertNotSentTo($user, StudentRegistered::class);
        $this->assertSame('student', $user->role->value);
        $this->assertSame('Student User', $user->name);
        $this->assertSame('Student', $user->first_name);
        $this->assertSame('User', $user->last_name);
        $this->assertNull($user->registration_paid_at);
        $this->assertSame('SK', $user->state_code);
        $this->assertSame(SpecialId::Coi, $user->special_id);
        $this->assertSame((int) now()->format('Y'), $user->registration_year);
        $this->assertSame(
            'SK-COI-'.now()->format('Y').'-01',
            $user->student_number,
        );
        $this->assertNotNull($user->studentProfile);
        $this->assertSame(RegistrationPurpose::TrainingAndPlacement, $user->studentProfile->registration_purpose);
    }

    public function test_registration_requires_state_special_id_and_purpose(): void
    {
        $response = $this->post(route('student.register.store'), [
            'first_name' => 'Student',
            'last_name' => 'User',
            'email' => 'student2@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'form_token' => Crypt::encryptString((string) (now()->timestamp - 10)),
        ]);

        $response->assertSessionHasErrors(['state', 'special_id', 'registration_purpose']);
        $this->assertGuest();
    }

    public function test_unknown_purpose_is_rejected(): void
    {
        $this->post(route('student.register.store'), $this->validPayload([
            'email' => 'student3@example.com',
            'registration_purpose' => 'hired',
        ]))->assertSessionHasErrors('registration_purpose');

        $this->assertGuest();
    }

    public function test_filled_honeypot_is_rejected(): void
    {
        $this->post(route('student.register.store'), $this->validPayload(['website' => 'https://spam.test']))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'student@example.com']);
    }

    public function test_instant_submission_is_rejected(): void
    {
        $this->post(route('student.register.store'), $this->validPayload([
            'form_token' => Crypt::encryptString((string) now()->timestamp),
        ]))->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_missing_or_tampered_form_token_is_rejected(): void
    {
        $this->post(route('student.register.store'), $this->validPayload(['form_token' => 'nope']))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_turnstile_failure_blocks_registration_when_configured(): void
    {
        config(['services.turnstile.secret' => 'secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

        $this->post(route('student.register.store'), $this->validPayload(['cf-turnstile-response' => 'bad']))
            ->assertSessionHasErrors('cf-turnstile-response');

        $this->assertGuest();
    }

    public function test_turnstile_success_allows_registration(): void
    {
        config(['services.turnstile.secret' => 'secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $this->post(route('student.register.store'), $this->validPayload(['cf-turnstile-response' => 'good']))
            ->assertRedirect(route('verification.notice', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_missing_turnstile_token_blocks_registration_when_configured(): void
    {
        config(['services.turnstile.secret' => 'secret']);

        $this->post(route('student.register.store'), $this->validPayload())
            ->assertSessionHasErrors('cf-turnstile-response');

        $this->assertGuest();
    }

    public function test_unverified_student_cannot_reach_dashboard(): void
    {
        $this->post(route('student.register.store'), $this->validPayload());

        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_welcome_notification_is_sent_once_after_verification(): void
    {
        $this->post(route('student.register.store'), $this->validPayload());
        $user = User::query()->where('email', 'student@example.com')->firstOrFail();

        Notification::fake();

        event(new Verified($user));

        Notification::assertSentToTimes($user, StudentRegistered::class, 1);
    }
}
