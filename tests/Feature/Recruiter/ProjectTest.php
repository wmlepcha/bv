<?php

namespace Tests\Feature\Recruiter;

use App\Models\RecruiterProject;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private User $recruiter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recruiter = User::factory()->recruiter()->create();
    }

    public function test_recruiter_creates_a_project_with_required_fields(): void
    {
        $this->actingAs($this->recruiter)
            ->post(route('recruiter.projects.store'), [
                'name' => 'Nurses for Germany',
                'country' => 'Germany',
                'sector' => 'Nursing',
                'timeline' => 'Within 3 months',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('recruiter_projects', ['user_id' => $this->recruiter->id, 'name' => 'Nurses for Germany']);

        $this->post(route('recruiter.projects.store'), ['name' => 'x'])
            ->assertSessionHasErrors(['country', 'sector', 'timeline']);
    }

    public function test_index_only_lists_own_projects_with_counts(): void
    {
        $mine = RecruiterProject::factory()->create(['user_id' => $this->recruiter->id]);
        RecruiterProject::factory()->create();
        $mine->candidates()->attach(StudentProfile::factory()->forPlacement()->enrolled()->create());

        $this->actingAs($this->recruiter)
            ->get(route('recruiter.projects.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('projects', 1)
                ->where('projects.0.candidates_count', 1));
    }

    public function test_candidate_can_be_added_removed_and_moved_between_projects(): void
    {
        $profile = StudentProfile::factory()->forPlacement()->enrolled()->create();
        $a = RecruiterProject::factory()->create(['user_id' => $this->recruiter->id]);
        $b = RecruiterProject::factory()->create(['user_id' => $this->recruiter->id]);

        $this->actingAs($this->recruiter);

        $this->post(route('recruiter.projects.candidates.store', [$a, $profile]))->assertRedirect();
        $this->post(route('recruiter.projects.candidates.store', [$a, $profile]))->assertRedirect();
        $this->assertSame(1, $a->candidates()->count());

        $this->get(route('recruiter.projects.show', $a))
            ->assertInertia(fn (Assert $page) => $page->has('candidates', 1)->has('otherProjects', 1));

        $this->post(route('recruiter.projects.candidates.move', [$a, $profile]), ['target_project_id' => $b->id])->assertRedirect();
        $this->assertSame(0, $a->candidates()->count());
        $this->assertSame(1, $b->candidates()->count());

        $this->delete(route('recruiter.projects.candidates.destroy', [$b, $profile]))->assertRedirect();
        $this->assertSame(0, $b->candidates()->count());
    }

    public function test_candidate_can_belong_to_multiple_projects_and_profile_reports_membership(): void
    {
        $profile = StudentProfile::factory()->forPlacement()->enrolled()->create();
        $a = RecruiterProject::factory()->create(['user_id' => $this->recruiter->id, 'name' => 'A']);
        $b = RecruiterProject::factory()->create(['user_id' => $this->recruiter->id, 'name' => 'B']);
        $a->candidates()->attach($profile);

        $this->actingAs($this->recruiter)
            ->get(route('recruiter.students.show', $profile))
            ->assertInertia(fn (Assert $page) => $page
                ->where('shortlistProjects.0.added', true)
                ->where('shortlistProjects.1.added', false));
    }

    public function test_other_recruiters_cannot_touch_a_project(): void
    {
        $project = RecruiterProject::factory()->create();
        $profile = StudentProfile::factory()->forPlacement()->enrolled()->create();

        $this->actingAs($this->recruiter);

        $this->get(route('recruiter.projects.show', $project))->assertNotFound();
        $this->put(route('recruiter.projects.update', $project), ['name' => 'n', 'country' => 'c', 'sector' => 's', 'timeline' => 't'])->assertNotFound();
        $this->delete(route('recruiter.projects.destroy', $project))->assertNotFound();
        $this->post(route('recruiter.projects.candidates.store', [$project, $profile]))->assertNotFound();
    }

    public function test_cannot_move_to_someone_elses_project_and_hired_cannot_be_shortlisted(): void
    {
        $mine = RecruiterProject::factory()->create(['user_id' => $this->recruiter->id]);
        $theirs = RecruiterProject::factory()->create();
        $profile = StudentProfile::factory()->forPlacement()->enrolled()->create();
        $mine->candidates()->attach($profile);

        $this->actingAs($this->recruiter)
            ->post(route('recruiter.projects.candidates.move', [$mine, $profile]), ['target_project_id' => $theirs->id])
            ->assertSessionHasErrors('target_project_id');

        $hired = StudentProfile::factory()->forPlacement()->enrolled()->hired()->create();
        $this->post(route('recruiter.projects.candidates.store', [$mine, $hired]))->assertStatus(422);
    }

    public function test_project_can_be_updated_and_deleted_and_teachers_are_forbidden(): void
    {
        $project = RecruiterProject::factory()->create(['user_id' => $this->recruiter->id]);

        $this->actingAs($this->recruiter)
            ->put(route('recruiter.projects.update', $project), ['name' => 'Renamed', 'country' => 'Austria', 'sector' => 'Physio', 'timeline' => 'Q1'])
            ->assertRedirect();
        $this->assertSame('Renamed', $project->fresh()->name);

        $this->delete(route('recruiter.projects.destroy', $project))->assertRedirect(route('recruiter.projects.index'));
        $this->assertModelMissing($project);

        $this->actingAs(User::factory()->teacher()->create())
            ->get(route('recruiter.projects.index'))->assertForbidden();
    }
}
