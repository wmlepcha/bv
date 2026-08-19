<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccessGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaid_student_can_visit_dashboard(): void
    {
        $user = User::factory()->student()->unpaid()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_unpaid_student_dashboard_includes_registration_plans(): void
    {
        $user = User::factory()->student()->unpaid()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/dashboard')
                ->has('registrationPlans', 2)
            );
    }

    public function test_expired_student_cannot_access_student_feature_routes(): void
    {
        $user = User::factory()->student()->expired()->create();

        $response = $this->actingAs($user)->get(route('student.classes.index'));

        $response->assertForbidden();
    }

    public function test_unpaid_student_cannot_access_student_feature_routes(): void
    {
        $user = User::factory()->student()->unpaid()->create();

        $response = $this->actingAs($user)->get(route('student.classes.index'));

        $response->assertForbidden();
    }

    public function test_paid_student_can_access_student_feature_routes(): void
    {
        $user = User::factory()->student()->paid()->create();

        $response = $this->actingAs($user)->get(route('student.classes.index'));

        $response->assertOk();
    }
}
