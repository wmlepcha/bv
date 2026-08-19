<?php

namespace Tests\Feature\Auth;

use App\Enums\SpecialId;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class StudentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('student.register.create'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('auth/register')
            ->has('states')
            ->has('specialIds'));
    }

    public function test_students_can_register(): void
    {
        $response = $this->post(route('student.register.store'), [
            'first_name' => 'Student',
            'last_name' => 'User',
            'email' => 'student@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'state' => 'Sikkim',
            'special_id' => 'COI',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'student@example.com')->first();

        $this->assertNotNull($user);
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
    }

    public function test_registration_requires_state_and_special_id(): void
    {
        $response = $this->post(route('student.register.store'), [
            'first_name' => 'Student',
            'last_name' => 'User',
            'email' => 'student2@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['state', 'special_id']);
        $this->assertGuest();
    }
}
