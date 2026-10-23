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

class BatchShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_manager_can_view_batch_details(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create([
            'starts_on' => now()->startOfMonth(),
            'ends_on' => now()->addMonths(6),
        ]);

        $student = User::factory()->student()->paid()->create();
        BatchEnrollment::query()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        $assignment = Assignment::factory()->published()->create([
            'batch_id' => $batch->id,
            'title' => 'Week 1 Worksheet',
        ]);

        AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'status' => AssignmentSubmissionStatus::Graded,
            'final_score' => 8,
        ]);

        Assignment::factory()->create([
            'batch_id' => $batch->id,
            'status' => AssignmentStatus::Draft,
            'title' => 'Draft task',
        ]);

        $this->actingAs($manager)
            ->get(route('training-manager.batches.show', $batch))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/training-manager/batches/show')
                ->where('batch.id', $batch->id)
                ->where('stats.enrollments_count', 1)
                ->where('stats.total_assignments', 2)
                ->where('stats.published_assignments', 1)
                ->where('stats.submissions_count', 1)
                ->where('stats.graded_submissions_count', 1)
                ->has('setup.items', 5)
                ->has('assignments', 2)
                ->has('students', 1)
                ->has('submissions', 1)
            );
    }

    public function test_batch_index_includes_dates_and_setup_percent(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create([
            'starts_on' => now()->startOfMonth(),
            'ends_on' => now()->addMonths(3),
        ]);

        $this->actingAs($manager)
            ->get(route('training-manager.batches.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/training-manager/batches/index')
                ->has('batches', 1)
                ->where('batches.0.id', $batch->id)
                ->where('batches.0.starts_on', $batch->starts_on->format('Y-m-d'))
                ->where('batches.0.ends_on', $batch->ends_on->format('Y-m-d'))
                ->where('batches.0.setup_percent', 80)
            );
    }
}
