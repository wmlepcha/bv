<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
