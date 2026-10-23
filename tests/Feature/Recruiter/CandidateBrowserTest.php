<?php

namespace Tests\Feature\Recruiter;

use App\Enums\CourseLevel;
use App\Enums\Profession;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CandidateBrowserTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_cards_with_status_experience_and_level(): void
    {
        StudentProfile::factory()->forPlacement()->experienced()->hired()
            ->enrolled(Batch::factory()->create(['level' => CourseLevel::B1]))
            ->professions([Profession::Physiotherapist->value])->create();

        $this->actingAs(User::factory()->recruiter()->create())
            ->get(route('recruiter.students.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/recruiter/students/index')
                ->has('profiles.data', 1)
                ->where('profiles.data.0.placement_status', 'hired')
                ->where('profiles.data.0.viewable', false)
                ->where('profiles.data.0.experience', 'experienced')
                ->where('profiles.data.0.language_level', 'B1')
                ->where('profiles.data.0.professions', ['Physiotherapist']));
    }

    public function test_filters_narrow_the_list(): void
    {
        StudentProfile::factory()->forPlacement()->enrolled()->professions([Profession::NurseRegistration->value])->create();
        StudentProfile::factory()->forPlacement()->enrolled()->experienced()->professions([Profession::Physiotherapist->value])->create();
        $recruiter = User::factory()->recruiter()->create();

        $count = fn (array $query): int => count(
            $this->actingAs($recruiter)
                ->get(route('recruiter.students.index', $query))
                ->assertOk()
                ->viewData('page')['props']['profiles']['data'],
        );

        $this->assertSame(2, $count([]));
        $this->assertSame(1, $count(['profession' => 'nurse_registration']));
        $this->assertSame(1, $count(['experience' => 'fresher']));
        $this->assertSame(1, $count(['experience' => 'experienced']));
        $this->assertSame(0, $count(['profession' => 'physiotherapist', 'experience' => 'fresher']));
        $this->assertSame(0, $count(['level' => 'A1']));

        StudentProfile::factory()->forPlacement()->enrolled()->hired()->create();
        $this->assertSame(3, $count([]));
        $this->assertSame(1, $count(['status' => 'hired']));
        $this->assertSame(2, $count(['status' => 'open']));
    }

    public function test_unpaid_or_unenrolled_students_are_not_listed(): void
    {
        $unenrolled = StudentProfile::factory()->forPlacement()->create();
        $unpaid = StudentProfile::factory()->forPlacement()->enrolled()->create();
        $unpaid->user->forceFill(['registration_expires_at' => null])->save();
        StudentProfile::factory()->forPlacement()->enrolled()->create();
        $recruiter = User::factory()->recruiter()->create();

        $this->actingAs($recruiter)
            ->get(route('recruiter.students.index'))
            ->assertInertia(fn (Assert $page) => $page->has('profiles.data', 1));

        $this->get(route('recruiter.students.show', $unenrolled))->assertNotFound();
        $this->get(route('recruiter.students.show', $unpaid))->assertNotFound();
    }

    public function test_invalid_filter_is_rejected(): void
    {
        $this->actingAs(User::factory()->recruiter()->create())
            ->get(route('recruiter.students.index', ['profession' => 'astronaut']))
            ->assertSessionHasErrors('profession');
    }

    public function test_recruiter_sees_full_profile_and_language_progress_of_open_candidate(): void
    {
        $batch = Batch::factory()->create(['level' => CourseLevel::A2]);
        $profile = StudentProfile::factory()->forPlacement()->enrolled($batch)->create();

        $graded = Assignment::factory()->published()->create(['batch_id' => $batch->id, 'max_score' => 10, 'due_at' => now()->addDay()]);
        Assignment::factory()->published()->create(['batch_id' => $batch->id, 'due_at' => now()->addDay()]);
        Assignment::factory()->published()->create(['batch_id' => $batch->id, 'due_at' => now()->subDay()]);
        AssignmentSubmission::factory()->graded()->create([
            'assignment_id' => $graded->id,
            'student_id' => $profile->user_id,
            'final_score' => 8,
        ]);

        $this->actingAs(User::factory()->recruiter()->create())
            ->get(route('recruiter.students.show', $profile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('restricted', false)
                ->has('cv')
                ->where('language.course.level', 'A2')
                ->where('language.assignments.total', 3)
                ->where('language.assignments.graded', 1)
                ->where('language.assignments.pending', 2)
                ->where('language.assignments.overdue', 1)
                ->where('language.assignments.average_percent', 80)
                ->where('language.self_assessment', null));
    }

    public function test_hired_candidate_data_is_withheld_from_recruiters_and_employees(): void
    {
        $profile = StudentProfile::factory()->forPlacement()->enrolled()->hired()->create();

        foreach ([User::factory()->recruiter()->create(), User::factory()->employee()->create()] as $viewer) {
            $this->actingAs($viewer)
                ->get(route('recruiter.students.show', $profile))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('restricted', true)
                    ->missing('cv')
                    ->missing('language')
                    ->missing('assignment'));

            $this->postJson(route('recruiter.students.cv.translate', $profile), ['locale' => 'de'])
                ->assertForbidden();
        }
    }

    public function test_admin_still_sees_hired_candidate(): void
    {
        $profile = StudentProfile::factory()->forPlacement()->enrolled()->hired()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('recruiter.students.index'))
            ->assertInertia(fn (Assert $page) => $page->where('profiles.data.0.viewable', true));

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('recruiter.students.show', $profile))
            ->assertInertia(fn (Assert $page) => $page->where('restricted', false)->has('cv'));
    }

    public function test_recruiter_dashboard_shows_counts(): void
    {
        StudentProfile::factory()->forPlacement()->enrolled()->count(2)->create();
        StudentProfile::factory()->forPlacement()->enrolled()->hired()->create();

        $this->actingAs(User::factory()->recruiter()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/recruiter/dashboard')
                ->where('stats.open', 2)
                ->where('stats.hired', 1)
                ->where('stats.total', 3)
                ->has('recent', 2));
    }

    public function test_projects_page_is_for_recruiters_not_teachers(): void
    {
        $this->actingAs(User::factory()->recruiter()->create())
            ->get(route('recruiter.projects.index'))->assertOk();

        $this->actingAs(User::factory()->teacher()->create())
            ->get(route('recruiter.projects.index'))->assertForbidden();
    }
}
