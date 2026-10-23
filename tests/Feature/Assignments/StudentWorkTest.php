<?php

namespace Tests\Feature\Assignments;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentWorkTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_assignment_roster_includes_all_enrolled_students(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();
        $students = User::factory()->student()->paid()->count(3)->create();

        foreach ($students as $student) {
            BatchEnrollment::factory()->create([
                'batch_id' => $batch->id,
                'student_id' => $student->id,
            ]);
        }

        $assignment = Assignment::factory()->published()->create(['batch_id' => $batch->id]);
        $assignment->targets()->create(['scope' => 'batch', 'student_id' => null]);

        AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'student_id' => $students[0]->id,
        ]);
        AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'student_id' => $students[1]->id,
        ]);

        $this->actingAs($manager)
            ->get(route('training-manager.assignments.submissions', $assignment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/training-manager/assignments/submissions/index')
                ->has('roster', 3)
                ->where('summary.turned_in_count', 2)
                ->where('summary.assigned_count', 3)
            );
    }

    public function test_individual_target_roster_includes_only_targeted_students(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();
        $targeted = User::factory()->student()->paid()->create();
        $other = User::factory()->student()->paid()->create();

        BatchEnrollment::factory()->create([
            'batch_id' => $batch->id,
            'student_id' => $targeted->id,
        ]);
        BatchEnrollment::factory()->create([
            'batch_id' => $batch->id,
            'student_id' => $other->id,
        ]);

        $assignment = Assignment::factory()->published()->create(['batch_id' => $batch->id]);
        $assignment->targets()->delete();
        $assignment->targets()->create([
            'scope' => 'student',
            'student_id' => $targeted->id,
        ]);

        $this->actingAs($manager)
            ->get(route('training-manager.assignments.submissions', $assignment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('roster', 1)
                ->where('roster.0.student.id', $targeted->id)
                ->where('summary.assigned_count', 1)
            );
    }

    public function test_assignment_show_includes_student_work_props(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();
        $student = User::factory()->student()->paid()->create();
        BatchEnrollment::factory()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        $assignment = Assignment::factory()->published()->create(['batch_id' => $batch->id]);

        $this->actingAs($manager)
            ->get(route('training-manager.assignments.show', $assignment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('roster', 1)
                ->has('summary')
            );
    }

    public function test_faculty_can_view_submissions_roster(): void
    {
        $teacher = User::factory()->teacher()->create();
        $batch = Batch::factory()->create(['teacher_id' => $teacher->id]);
        $assignment = Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $teacher->id,
        ]);

        $this->actingAs($teacher)
            ->get(route('teacher.assignments.submissions', $assignment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/teacher/assignments/submissions/index')
                ->has('roster')
                ->has('summary')
            );
    }
}
