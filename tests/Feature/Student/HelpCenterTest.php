<?php

namespace Tests\Feature\Student;

use App\Enums\CourseLevel;
use App\Enums\CoursePace;
use App\Enums\FeedbackSkill;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\CourseFeedback;
use App\Models\User;
use App\Services\CourseFeedbackService;
use App\Services\RegistrationAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HelpCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_eligible_student_sees_help_center_with_candidate_details(): void
    {
        [$student, $batch] = $this->enrolledStudent(activatedDaysAgo: 28);

        $this->actingAs($student)
            ->get(route('student.help-center.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/student/help-center')
                ->where('feedback.open_form_number', 1)
                ->where('feedback.candidate.batch_name', $batch->name)
                ->where('feedback.candidate.trainer_name', $batch->teacher->name)
                ->where('feedback.candidate.center_name', $batch->center->name)
                ->where('feedback.default_level', 'B1')
                ->has('feedback.rating_questions', 8)
                ->has('feedback.forms', 2)
                ->where('feedback.forms.0.status', 'open')
                ->where('feedback.forms.1.status', 'locked'));
    }

    public function test_first_form_is_locked_until_four_weeks_after_dashboard_activation(): void
    {
        [$student] = $this->enrolledStudent(activatedDaysAgo: 27);

        $this->actingAs($student)
            ->get(route('student.help-center.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('feedback.open_form_number', null)
                ->where('feedback.forms.0.status', 'locked')
                ->where('feedback.forms.0.available_at_label', $student->dashboard_activated_at->addWeeks(4)->format('j M Y'))
                ->where('feedback.forms.1.status', 'locked')
                ->where('feedback.forms.1.available_at_label', null));

        $this->actingAs($student)
            ->post(route('student.help-center.feedback.store'), $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('course_feedback', 0);
    }

    public function test_student_can_submit_first_form_four_weeks_after_dashboard_activation(): void
    {
        [$student, $batch] = $this->enrolledStudent(activatedDaysAgo: 28);

        $this->actingAs($student)
            ->from(route('student.help-center.index'))
            ->post(route('student.help-center.feedback.store'), $this->validPayload())
            ->assertRedirect(route('student.help-center.index'))
            ->assertSessionHas('success');

        $feedback = CourseFeedback::query()->sole();

        $this->assertSame(1, $feedback->form_number);
        $this->assertSame($student->id, $feedback->student_id);
        $this->assertSame($batch->id, $feedback->batch_id);
        $this->assertSame($batch->teacher_id, $feedback->teacher_id);
        $this->assertSame($batch->center_id, $feedback->center_id);
        $this->assertSame(CourseLevel::B1, $feedback->level);
        $this->assertSame(FeedbackSkill::Speaking, $feedback->most_improved_skill);
        $this->assertSame(CoursePace::Appropriate, $feedback->pace);
        $this->assertSame(5, $feedback->overall_satisfaction);
    }

    public function test_second_form_stays_locked_until_five_weeks_after_first_form_submission(): void
    {
        [$student] = $this->enrolledStudent(activatedDaysAgo: 28);

        $this->actingAs($student)
            ->post(route('student.help-center.feedback.store'), $this->validPayload())
            ->assertSessionHasNoErrors();

        $this->actingAs($student)
            ->get(route('student.help-center.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('feedback.open_form_number', null)
                ->where('feedback.forms.0.status', 'submitted')
                ->where('feedback.forms.0.level', 'B1')
                ->where('feedback.forms.1.status', 'locked'));

        $this->actingAs($student)
            ->post(route('student.help-center.feedback.store'), $this->validPayload())
            ->assertForbidden();

        $this->travel(34)->days();

        $this->actingAs($student)
            ->post(route('student.help-center.feedback.store'), $this->validPayload())
            ->assertForbidden();

        $this->travel(1)->days();

        $this->actingAs($student)
            ->post(route('student.help-center.feedback.store'), $this->validPayload())
            ->assertSessionHasNoErrors();

        $this->assertSame([1, 2], CourseFeedback::query()->orderBy('form_number')->pluck('form_number')->all());
    }

    public function test_second_form_does_not_open_just_from_time_since_activation(): void
    {
        [$student] = $this->enrolledStudent(activatedDaysAgo: 100);

        $this->actingAs($student)
            ->get(route('student.help-center.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('feedback.open_form_number', 1)
                ->where('feedback.forms.1.status', 'locked')
                ->where('feedback.forms.1.available_at_label', null));

        $this->actingAs($student)
            ->post(route('student.help-center.feedback.store'), $this->validPayload())
            ->assertSessionHasNoErrors();

        $this->actingAs($student)
            ->get(route('student.help-center.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('feedback.open_form_number', null)
                ->where('feedback.forms.0.status', 'submitted')
                ->where('feedback.forms.1.status', 'locked'));

        $this->actingAs($student)
            ->post(route('student.help-center.feedback.store'), $this->validPayload())
            ->assertForbidden();

        $this->travel(35)->days();

        $this->actingAs($student)
            ->post(route('student.help-center.feedback.store'), $this->validPayload())
            ->assertSessionHasNoErrors();

        $this->assertSame([1, 2], CourseFeedback::query()->orderBy('form_number')->pluck('form_number')->all());

        $this->actingAs($student)
            ->get(route('student.help-center.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('feedback.open_form_number', null)
                ->where('feedback.forms.1.status', 'submitted'));

        $this->actingAs($student)
            ->post(route('student.help-center.feedback.store'), $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('course_feedback', 2);
    }

    public function test_renewing_registration_does_not_restart_the_feedback_schedule(): void
    {
        [$student] = $this->enrolledStudent(activatedDaysAgo: 20);
        $activatedAt = $student->dashboard_activated_at;

        app(RegistrationAccessService::class)->extendAccess($student, 12);

        $this->assertTrue($student->fresh()->dashboard_activated_at->equalTo($activatedAt));
        $this->assertTrue($student->fresh()->registration_paid_at->isToday());
    }

    public function test_first_activation_starts_the_feedback_schedule(): void
    {
        $student = User::factory()->student()->unpaid()->create();

        app(RegistrationAccessService::class)->extendAccess($student, 12);

        $this->assertTrue($student->fresh()->dashboard_activated_at->isToday());
    }

    public function test_submission_is_validated(): void
    {
        [$student] = $this->enrolledStudent(activatedDaysAgo: 28);

        $this->actingAs($student)
            ->post(route('student.help-center.feedback.store'), [
                ...$this->validPayload(),
                'level' => 'C1',
                'trainer_quality' => 6,
                'confidence' => null,
                'pace' => 'very_slow',
                'liked_most' => '',
            ])
            ->assertSessionHasErrors(['level', 'trainer_quality', 'confidence', 'pace', 'liked_most']);

        $this->assertDatabaseCount('course_feedback', 0);
    }

    public function test_text_answers_are_limited_to_800_characters(): void
    {
        [$student] = $this->enrolledStudent(activatedDaysAgo: 28);

        $this->actingAs($student)
            ->post(route('student.help-center.feedback.store'), [
                ...$this->validPayload(),
                'liked_most' => str_repeat('a', 801),
                'improvement_suggestion' => str_repeat('b', 801),
            ])
            ->assertSessionHasErrors(['liked_most', 'improvement_suggestion']);

        $this->actingAs($student)
            ->post(route('student.help-center.feedback.store'), [
                ...$this->validPayload(),
                'liked_most' => str_repeat('a', 800),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(800, mb_strlen(CourseFeedback::query()->sole()->liked_most));
    }

    public function test_html_in_text_answers_is_rejected(): void
    {
        [$student] = $this->enrolledStudent(activatedDaysAgo: 28);

        $this->actingAs($student)
            ->post(route('student.help-center.feedback.store'), [
                ...$this->validPayload(),
                'liked_most' => '<script>alert(1)</script>',
            ])
            ->assertSessionHasErrors('liked_most');

        $this->assertDatabaseCount('course_feedback', 0);
    }

    public function test_text_answers_are_trimmed_and_whitespace_only_answers_are_rejected(): void
    {
        [$student] = $this->enrolledStudent(activatedDaysAgo: 28);

        $this->actingAs($student)
            ->post(route('student.help-center.feedback.store'), [...$this->validPayload(), 'liked_most' => "   \u{0007}  "])
            ->assertSessionHasErrors('liked_most');

        $this->actingAs($student)
            ->post(route('student.help-center.feedback.store'), [...$this->validPayload(), 'liked_most' => "  Great classes\u{0000}  "])
            ->assertSessionHasNoErrors();

        $this->assertSame('Great classes', CourseFeedback::query()->sole()->liked_most);
    }

    public function test_only_expected_fields_are_stored(): void
    {
        [$student, $batch] = $this->enrolledStudent(activatedDaysAgo: 28);
        $otherStudent = User::factory()->student()->paid()->create();

        $this->actingAs($student)
            ->post(route('student.help-center.feedback.store'), [
                ...$this->validPayload(),
                'student_id' => $otherStudent->id,
                'batch_id' => 999,
            ])
            ->assertSessionHasNoErrors();

        $feedback = CourseFeedback::query()->sole();

        $this->assertSame($student->id, $feedback->student_id);
        $this->assertSame($batch->id, $feedback->batch_id);
    }

    public function test_simultaneous_duplicate_submission_returns_validation_error_instead_of_crashing(): void
    {
        [$student] = $this->enrolledStudent(activatedDaysAgo: 28);
        $service = app(CourseFeedbackService::class);

        $service->submit($student, $this->validPayload());

        $this->expectException(ValidationException::class);

        $service->submit($student, $this->validPayload());
    }

    public function test_feedback_submissions_are_rate_limited(): void
    {
        [$student] = $this->enrolledStudent(activatedDaysAgo: 28);

        foreach (range(1, 5) as $attempt) {
            $this->actingAs($student)
                ->post(route('student.help-center.feedback.store'), [...$this->validPayload(), 'level' => 'C1'])
                ->assertSessionHasErrors('level');
        }

        $this->actingAs($student)
            ->post(route('student.help-center.feedback.store'), $this->validPayload())
            ->assertTooManyRequests();
    }

    public function test_help_center_exposes_the_answer_length_limit(): void
    {
        [$student] = $this->enrolledStudent(activatedDaysAgo: 28);

        $this->actingAs($student)
            ->get(route('student.help-center.index'))
            ->assertInertia(fn (Assert $page) => $page->where('feedback.max_answer_length', 800));
    }

    public function test_same_level_can_be_reported_on_different_forms(): void
    {
        [$student] = $this->enrolledStudent(activatedDaysAgo: 28);

        CourseFeedback::factory()->create([
            'student_id' => $student->id,
            'form_number' => 1,
            'level' => CourseLevel::B1,
        ]);

        $this->travel(35)->days();

        $this->actingAs($student)
            ->post(route('student.help-center.feedback.store'), $this->validPayload())
            ->assertSessionHasNoErrors();

        $this->assertSame(2, CourseFeedback::query()->latest('id')->first()->form_number);
    }

    public function test_unpaid_student_cannot_access_help_center(): void
    {
        $student = User::factory()->student()->unpaid()->create();

        $this->actingAs($student)
            ->get(route('student.help-center.index'))
            ->assertForbidden();
    }

    public function test_non_students_cannot_access_help_center(): void
    {
        $manager = User::factory()->trainingManager()->create();

        $this->actingAs($manager)
            ->get(route('student.help-center.index'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('student.help-center.feedback.store'), $this->validPayload())
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Batch}
     */
    private function enrolledStudent(int $activatedDaysAgo): array
    {
        $batch = Batch::factory()->create();
        $student = User::factory()->student()->paid()->create([
            'dashboard_activated_at' => now()->subDays($activatedDaysAgo),
        ]);

        BatchEnrollment::query()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        return [$student, $batch];
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'level' => 'B1',
            'overall_satisfaction' => 5,
            'trainer_quality' => 4,
            'explanation_clarity' => 4,
            'class_engagement' => 5,
            'materials_usefulness' => 3,
            'skills_improvement' => 4,
            'confidence' => 4,
            'most_improved_skill' => 'speaking',
            'needs_improvement_skill' => 'grammar',
            'pace' => 'appropriate',
            'support_satisfaction' => 5,
            'liked_most' => 'Interactive speaking sessions.',
            'improvement_suggestion' => 'More grammar practice.',
        ];
    }
}
