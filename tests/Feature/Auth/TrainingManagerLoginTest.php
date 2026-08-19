<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TrainingManagerLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_manager_login_page_renders_without_signup(): void
    {
        $this->get(route('training-manager.login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/training-manager-login')
                ->where('canResetPassword', true)
                ->missing('portal')
            );
    }

    public function test_training_manager_can_login_via_training_manager_portal(): void
    {
        $user = User::factory()->trainingManager()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'portal' => 'training_manager',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('training-manager.dashboard', absolute: false));
    }

    public function test_training_manager_cannot_login_through_teacher_portal(): void
    {
        $user = User::factory()->trainingManager()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'portal' => 'teacher',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_teacher_cannot_login_through_training_manager_portal(): void
    {
        $user = User::factory()->teacher()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'portal' => 'training_manager',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_authenticated_training_manager_is_redirected_from_login_entry(): void
    {
        $user = User::factory()->trainingManager()->create();

        $this->actingAs($user)
            ->get(route('training-manager.login'))
            ->assertRedirect(route('training-manager.dashboard'));
    }
}
