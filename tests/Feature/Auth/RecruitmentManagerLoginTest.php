<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\PortalLoginUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RecruitmentManagerLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_lives_at_its_own_route_without_signup(): void
    {
        $this->assertSame('/bv-rm-login', route('recruitment-manager.login', absolute: false));

        $this->get(route('recruitment-manager.login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/recruitment-manager-login')
                ->where('canResetPassword', true)
                ->missing('portal'));
    }

    public function test_manager_signs_in_through_their_portal_and_lands_on_the_dashboard(): void
    {
        $user = User::factory()->recruitmentManager()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'portal' => 'recruitment_manager'])
            ->assertRedirect(route('recruitment-manager.dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_manager_is_refused_on_every_other_portal(): void
    {
        $user = User::factory()->recruitmentManager()->create();

        foreach (['student', 'staff', 'teacher', 'training_manager'] as $portal) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'portal' => $portal])
                ->assertSessionHasErrors('email');
            $this->assertGuest();
        }
    }

    public function test_other_roles_cannot_use_the_manager_portal(): void
    {
        foreach ([User::factory()->recruiter()->create(), User::factory()->admin()->create(), User::factory()->teacher()->create(), User::factory()->student()->create(), User::factory()->trainingManager()->create()] as $user) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'portal' => 'recruitment_manager'])
                ->assertSessionHasErrors('email');
            $this->assertGuest();
        }
    }

    public function test_wrong_password_and_signed_in_visitors_are_handled(): void
    {
        $manager = User::factory()->recruitmentManager()->create();

        $this->post(route('login.store'), ['email' => $manager->email, 'password' => 'nope', 'portal' => 'recruitment_manager'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($manager)->get(route('recruitment-manager.login'))->assertRedirect(route('recruitment-manager.dashboard'));
        $this->actingAs(User::factory()->recruiter()->create())->get(route('recruitment-manager.login'))->assertForbidden();
    }

    public function test_login_links_for_the_role_point_to_the_manager_route(): void
    {
        $this->assertSame(url('/bv-rm-login'), PortalLoginUrl::forUser(User::factory()->recruitmentManager()->create()));
    }
}
