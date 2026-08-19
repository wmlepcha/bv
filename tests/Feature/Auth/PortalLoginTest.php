<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_shows_portal_without_query(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_student_cannot_login_through_staff_portal(): void
    {
        $user = User::factory()->student()->paid()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'portal' => 'staff',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_recruiter_cannot_login_through_student_portal(): void
    {
        $user = User::factory()->recruiter()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'portal' => 'student',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_recruiter_can_login_through_staff_portal(): void
    {
        $user = User::factory()->recruiter()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'portal' => 'staff',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_teacher_cannot_login_through_student_portal(): void
    {
        $user = User::factory()->teacher()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'portal' => 'student',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_teacher_cannot_login_through_staff_portal(): void
    {
        $user = User::factory()->teacher()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'portal' => 'staff',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_recruiter_cannot_login_through_teacher_portal(): void
    {
        $user = User::factory()->recruiter()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'portal' => 'teacher',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_teacher_can_login_through_teacher_portal(): void
    {
        $user = User::factory()->teacher()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'portal' => 'teacher',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
