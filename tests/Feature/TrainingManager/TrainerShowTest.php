<?php

namespace Tests\Feature\TrainingManager;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentSubmissionStatus;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TrainerShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_manager_can_view_trainer_details(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $trainer = User::factory()->teacher()->create([
            'name' => 'Demo Teacher',
            'email' => 'teacher@demo.bilingualvitae.com',
        ]);

        $batch = Batch::factory()->create([
            'teacher_id' => $trainer->id,
        ]);

        $student = User::factory()->student()->paid()->create();
        BatchEnrollment::query()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        $publishedAssignment = Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $trainer->id,
            'title' => 'Week 1 Worksheet',
        ]);

        AssignmentSubmission::factory()->create([
            'assignment_id' => $publishedAssignment->id,
            'student_id' => $student->id,
            'status' => AssignmentSubmissionStatus::Graded,
            'final_score' => 8,
        ]);

        Assignment::factory()->create([
            'batch_id' => $batch->id,
            'created_by' => $trainer->id,
            'status' => AssignmentStatus::Draft,
            'title' => 'Draft task',
        ]);

        $this->actingAs($manager)
            ->get(route('training-manager.trainers.show', $trainer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/training-manager/trainers/show')
                ->where('trainer.id', $trainer->id)
                ->where('trainer.name', 'Demo Teacher')
                ->where('stats.batches_count', 1)
                ->where('stats.students_count', 1)
                ->where('stats.total_assignments', 2)
                ->where('stats.published_assignments', 1)
                ->where('stats.submissions_count', 1)
                ->where('stats.graded_submissions_count', 1)
                ->has('batches', 1)
                ->has('assignments', 2)
                ->has('students', 1)
                ->has('remarks', 0)
            );
    }

    public function test_trainer_index_includes_summary_counts(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $trainer = User::factory()->teacher()->create();

        $batch = Batch::factory()->create([
            'teacher_id' => $trainer->id,
        ]);

        $student = User::factory()->student()->paid()->create();
        BatchEnrollment::query()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'created_by' => $trainer->id,
        ]);

        Assignment::factory()->create([
            'batch_id' => $batch->id,
            'created_by' => $trainer->id,
        ]);

        $this->actingAs($manager)
            ->get(route('training-manager.trainers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/training-manager/trainers/index')
                ->has('trainers', 1)
                ->where('trainers.0.id', $trainer->id)
                ->where('trainers.0.batches_count', 1)
                ->where('trainers.0.students_count', 1)
                ->where('trainers.0.assignments_count', 2)
            );
    }

    public function test_non_teacher_user_returns_not_found_on_trainer_show(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $student = User::factory()->student()->paid()->create();

        $this->actingAs($manager)
            ->get(route('training-manager.trainers.show', $student))
            ->assertNotFound();
    }
}
