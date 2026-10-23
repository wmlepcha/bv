<?php

namespace Tests\Feature\TrainingManager;

use App\Models\Batch;
use App\Models\CourseFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CourseFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_manager_can_list_feedback(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();
        $feedback = CourseFeedback::factory()->create([
            'batch_id' => $batch->id,
            'teacher_id' => $batch->teacher_id,
            'center_id' => $batch->center_id,
        ]);

        $this->actingAs($manager)
            ->get(route('training-manager.feedback.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/training-manager/feedback/index')
                ->has('feedback', 1)
                ->where('feedback.0.id', $feedback->id)
                ->where('feedback.0.batch_name', $batch->name)
                ->where('feedback.0.trainer_name', $batch->teacher->name)
                ->where('feedback.0.average_rating', fn (int|float $rating): bool => (float) $rating === $feedback->averageRating()));
    }

    public function test_training_manager_can_view_feedback_details(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $feedback = CourseFeedback::factory()->create(['liked_most' => 'The speaking practice.']);

        $this->actingAs($manager)
            ->get(route('training-manager.feedback.show', $feedback))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/training-manager/feedback/show')
                ->where('feedback.id', $feedback->id)
                ->where('feedback.liked_most', 'The speaking practice.')
                ->has('feedback.ratings', 8));
    }

    public function test_other_roles_cannot_view_feedback(): void
    {
        $feedback = CourseFeedback::factory()->create();

        foreach ([User::factory()->student()->paid()->create(), User::factory()->teacher()->create()] as $user) {
            $this->actingAs($user)
                ->get(route('training-manager.feedback.index'))
                ->assertForbidden();

            $this->actingAs($user)
                ->get(route('training-manager.feedback.show', $feedback))
                ->assertForbidden();
        }
    }
}
