<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_recruiter_cannot_access_student_classes(): void
    {
        $user = User::factory()->recruiter()->create();

        $response = $this->actingAs($user)->get(route('student.classes.index'));

        $response->assertForbidden();
    }

    public function test_student_cannot_access_recruiter_students(): void
    {
        $user = User::factory()->student()->paid()->create();

        $response = $this->actingAs($user)->get(route('recruiter.students.index'));

        $response->assertForbidden();
    }
}
