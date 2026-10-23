<?php

namespace Tests\Feature\Batches;

use App\Enums\CourseLanguage;
use App\Enums\CourseLevel;
use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\BatchEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use RuntimeException;
use Tests\TestCase;

class BatchAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_index_only_shows_enrolled_students(): void
    {
        $teacher = User::factory()->teacher()->create(['email_verified_at' => now()]);
        $otherTeacher = User::factory()->teacher()->create();

        $mine = User::factory()->paid()->create(['role' => UserRole::Student]);
        $theirs = User::factory()->paid()->create(['role' => UserRole::Student]);
        $unassigned = User::factory()->paid()->create(['role' => UserRole::Student]);

        $myProfile = StudentProfile::query()->create(['user_id' => $mine->id, 'headline' => 'Mine']);
        StudentProfile::query()->create(['user_id' => $theirs->id, 'headline' => 'Theirs']);
        StudentProfile::query()->create(['user_id' => $unassigned->id, 'headline' => 'Free']);

        $myBatch = Batch::factory()->create([
            'teacher_id' => $teacher->id,
            'language' => CourseLanguage::German,
            'level' => CourseLevel::B1,
        ]);
        $otherBatch = Batch::factory()->create([
            'teacher_id' => $otherTeacher->id,
            'language' => CourseLanguage::German,
            'level' => CourseLevel::B1,
        ]);

        BatchEnrollment::factory()->create(['batch_id' => $myBatch->id, 'student_id' => $mine->id]);
        BatchEnrollment::factory()->create(['batch_id' => $otherBatch->id, 'student_id' => $theirs->id]);

        $this->actingAs($teacher)
            ->get(route('recruiter.students.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('app/recruiter/students/index')
                ->has('profiles.data', 1)
                ->where('profiles.data.0.id', $myProfile->id));
    }

    public function test_recruiter_still_sees_all_students(): void
    {
        $recruiter = User::factory()->recruiter()->create(['email_verified_at' => now()]);
        $a = User::factory()->paid()->create(['role' => UserRole::Student]);
        $b = User::factory()->paid()->create(['role' => UserRole::Student]);

        StudentProfile::query()->create(['user_id' => $a->id, 'registration_purpose' => 'training_and_placement']);
        StudentProfile::query()->create(['user_id' => $b->id, 'registration_purpose' => 'training_and_placement']);
        BatchEnrollment::factory()->create(['student_id' => $a->id]);
        BatchEnrollment::factory()->create(['student_id' => $b->id]);

        $this->actingAs($recruiter)
            ->get(route('recruiter.students.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('app/recruiter/students/index')
                ->has('profiles.data', 2));
    }

    public function test_teacher_cannot_view_student_outside_their_batch(): void
    {
        $teacher = User::factory()->teacher()->create(['email_verified_at' => now()]);
        $student = User::factory()->paid()->create(['role' => UserRole::Student]);
        $profile = StudentProfile::query()->create(['user_id' => $student->id]);

        $this->actingAs($teacher)
            ->get(route('recruiter.students.show', $profile))
            ->assertForbidden();
    }

    public function test_teacher_can_transfer_to_same_language_and_level_batch(): void
    {
        $teacherA = User::factory()->teacher()->create(['email_verified_at' => now()]);
        $teacherB = User::factory()->teacher()->create();
        $student = User::factory()->paid()->create(['role' => UserRole::Student]);
        $profile = StudentProfile::query()->create(['user_id' => $student->id]);

        $source = Batch::factory()->create([
            'name' => 'German B1 A',
            'teacher_id' => $teacherA->id,
            'language' => CourseLanguage::German,
            'level' => CourseLevel::B1,
        ]);
        $target = Batch::factory()->create([
            'name' => 'German B1 B',
            'teacher_id' => $teacherB->id,
            'language' => CourseLanguage::German,
            'level' => CourseLevel::B1,
        ]);

        BatchEnrollment::factory()->create([
            'batch_id' => $source->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($teacherA)
            ->post(route('recruiter.students.transfer', $profile), [
                'target_batch_id' => $target->id,
            ])
            ->assertRedirect(route('recruiter.students.index'));

        $enrollment = BatchEnrollment::query()->where('student_id', $student->id)->first();

        $this->assertNotNull($enrollment);
        $this->assertSame($target->id, $enrollment->batch_id);
        $this->assertSame($source->id, $enrollment->transferred_from_batch_id);
        $this->assertSame($teacherA->id, $enrollment->transferred_by);
        $this->assertNotNull($enrollment->transferred_at);
    }

    public function test_teacher_cannot_transfer_to_different_level(): void
    {
        $teacherA = User::factory()->teacher()->create(['email_verified_at' => now()]);
        $teacherB = User::factory()->teacher()->create();
        $student = User::factory()->paid()->create(['role' => UserRole::Student]);
        $profile = StudentProfile::query()->create(['user_id' => $student->id]);

        $source = Batch::factory()->create([
            'teacher_id' => $teacherA->id,
            'language' => CourseLanguage::German,
            'level' => CourseLevel::B1,
        ]);
        $target = Batch::factory()->create([
            'teacher_id' => $teacherB->id,
            'language' => CourseLanguage::German,
            'level' => CourseLevel::B2,
        ]);

        BatchEnrollment::factory()->create([
            'batch_id' => $source->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($teacherA)
            ->from(route('recruiter.students.show', $profile))
            ->post(route('recruiter.students.transfer', $profile), [
                'target_batch_id' => $target->id,
            ])
            ->assertRedirect(route('recruiter.students.show', $profile))
            ->assertSessionHasErrors('target_batch_id');

        $this->assertSame(
            $source->id,
            BatchEnrollment::query()->where('student_id', $student->id)->value('batch_id'),
        );
    }

    public function test_teacher_cannot_transfer_student_not_in_their_batch(): void
    {
        $teacherA = User::factory()->teacher()->create(['email_verified_at' => now()]);
        $teacherB = User::factory()->teacher()->create(['email_verified_at' => now()]);
        $student = User::factory()->paid()->create(['role' => UserRole::Student]);
        $profile = StudentProfile::query()->create(['user_id' => $student->id]);

        $source = Batch::factory()->create([
            'teacher_id' => $teacherB->id,
            'language' => CourseLanguage::German,
            'level' => CourseLevel::B1,
        ]);
        $target = Batch::factory()->create([
            'teacher_id' => $teacherA->id,
            'language' => CourseLanguage::German,
            'level' => CourseLevel::B1,
        ]);

        BatchEnrollment::factory()->create([
            'batch_id' => $source->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($teacherA)
            ->post(route('recruiter.students.transfer', $profile), [
                'target_batch_id' => $target->id,
            ])
            ->assertForbidden();
    }

    public function test_cannot_enroll_student_in_second_batch(): void
    {
        $student = User::factory()->paid()->create(['role' => UserRole::Student]);
        $first = Batch::factory()->create();
        $second = Batch::factory()->create();

        $service = app(BatchEnrollmentService::class);
        $service->enroll($first, $student);

        $this->expectException(RuntimeException::class);
        $service->enroll($second, $student);
    }

    public function test_recruiter_cannot_use_transfer_endpoint(): void
    {
        $recruiter = User::factory()->recruiter()->create(['email_verified_at' => now()]);
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->paid()->create(['role' => UserRole::Student]);
        $profile = StudentProfile::query()->create(['user_id' => $student->id]);

        $source = Batch::factory()->create([
            'teacher_id' => $teacher->id,
            'language' => CourseLanguage::German,
            'level' => CourseLevel::B1,
        ]);
        $target = Batch::factory()->create([
            'language' => CourseLanguage::German,
            'level' => CourseLevel::B1,
        ]);

        BatchEnrollment::factory()->create([
            'batch_id' => $source->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($recruiter)
            ->post(route('recruiter.students.transfer', $profile), [
                'target_batch_id' => $target->id,
            ])
            ->assertForbidden();
    }
}
