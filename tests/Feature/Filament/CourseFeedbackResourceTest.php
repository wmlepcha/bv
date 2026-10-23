<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\CourseFeedback\Pages\ListCourseFeedback;
use App\Models\CourseFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CourseFeedbackResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_course_feedback(): void
    {
        $admin = User::factory()->admin()->create();
        $feedback = CourseFeedback::factory()->count(2)->create();

        Livewire::actingAs($admin)
            ->test(ListCourseFeedback::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($feedback);
    }

    public function test_admin_can_view_course_feedback_record(): void
    {
        $admin = User::factory()->admin()->create();
        $feedback = CourseFeedback::factory()->create(['liked_most' => 'Great trainer']);

        Livewire::actingAs($admin)
            ->test(ListCourseFeedback::class)
            ->mountTableAction('view', $feedback)
            ->assertMountedActionModalSee('Great trainer');
    }

    public function test_non_admin_cannot_access_course_feedback_resource(): void
    {
        $manager = User::factory()->trainingManager()->create();

        $this->actingAs($manager)
            ->get(route('filament.bv-admin.resources.course-feedback.index'))
            ->assertForbidden();
    }
}
