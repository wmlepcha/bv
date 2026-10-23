<?php

namespace Tests\Feature\Student;

use App\Enums\AssignmentStatus;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\EnrolledInBatch;
use App\Notifications\StudentRegistered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_student_dashboard_includes_profile_batch_trainer_and_notifications(): void
    {
        $trainer = User::factory()->teacher()->create(['name' => 'Demo Teacher']);
        $batch = Batch::factory()->create([
            'teacher_id' => $trainer->id,
            'name' => 'German B1 Morning',
        ]);

        $student = User::factory()->student()->paid()->create([
            'first_name' => 'Alex',
            'last_name' => 'Student',
        ]);

        StudentProfile::query()->create([
            'user_id' => $student->id,
            'headline' => 'Aspiring nurse',
        ]);

        BatchEnrollment::query()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $trainer->id,
            'title' => 'Week 1 Worksheet',
        ]);

        Assignment::factory()->create([
            'batch_id' => $batch->id,
            'created_by' => $trainer->id,
            'status' => AssignmentStatus::Draft,
        ]);

        $student->notifyNow(new StudentRegistered);

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/student/dashboard')
                ->where('profile.name', $student->name)
                ->where('profile.headline', 'Aspiring nurse')
                ->where('batch.name', 'German B1 Morning')
                ->where('trainer.name', 'Demo Teacher')
                ->where('stats.total_assignments', 1)
                ->where('stats.pending_assignments', 1)
                ->has('notifications', 1)
                ->has('recent_assignments', 1)
            );
    }

    public function test_unpaid_student_dashboard_shows_notifications_without_batch_data(): void
    {
        $student = User::factory()->student()->unpaid()->create();

        $student->notifyNow(new StudentRegistered);

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/student/dashboard')
                ->where('batch', null)
                ->where('trainer', null)
                ->where('stats.total_assignments', 0)
                ->has('notifications', 1)
            );
    }

    public function test_student_notifications_page_lists_all_notifications_with_unread_count(): void
    {
        $student = User::factory()->student()->paid()->create();
        $student->notify(new EnrolledInBatch(Batch::factory()->create()));

        $this->actingAs($student)
            ->get(route('student.notifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/student/notifications')
                ->has('notifications', 1)
                ->where('unread_count', 1)
                ->where('notifications.0.kind', 'info'));
    }

    public function test_non_student_still_sees_generic_dashboard(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('app/dashboard'));
    }
}
