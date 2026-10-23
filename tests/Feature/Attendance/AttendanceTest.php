<?php

namespace Tests\Feature\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRecordChange;
use App\Models\AttendanceSession;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\LowAttendanceWarning;
use App\Services\AttendanceSummaryService;
use App\Services\AttendanceTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Batch $batch;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->create();
        $this->batch = Batch::factory()->create(['teacher_id' => $this->teacher->id]);
        $this->student = User::factory()->student()->paid()->create();
        BatchEnrollment::factory()->create(['batch_id' => $this->batch->id, 'student_id' => $this->student->id]);
    }

    private function openSession(): AttendanceSession
    {
        $this->actingAs($this->teacher)
            ->post(route('teacher.attendance.store', $this->batch))
            ->assertRedirect();

        return AttendanceSession::query()->whereNull('closed_at')->latest('id')->firstOrFail();
    }

    private function token(AttendanceSession $session): string
    {
        return app(AttendanceTokenService::class)->current($session)['token'];
    }

    public function test_trainer_opens_one_session_per_batch_and_only_for_own_batches(): void
    {
        $first = $this->openSession();
        $this->post(route('teacher.attendance.store', $this->batch));
        $this->assertSame(1, AttendanceSession::query()->count());

        $this->get(route('teacher.attendance.show', $first))
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/teacher/attendance/show')
                ->where('live.total', 1)
                ->where('live.present_count', 0)
                ->has('live.token.token'));

        $other = Batch::factory()->create();
        $this->post(route('teacher.attendance.store', $other))->assertNotFound();
        $this->actingAs(User::factory()->teacher()->create())
            ->get(route('teacher.attendance.show', $first))->assertNotFound();
    }

    public function test_student_scan_marks_present_once_and_is_idempotent(): void
    {
        $session = $this->openSession();
        $token = $this->token($session);

        $this->actingAs($this->student)
            ->postJson(route('student.attendance.scan'), ['token' => $token])
            ->assertOk()->assertJson(['result' => 'marked']);
        $this->postJson(route('student.attendance.scan'), ['token' => $token])
            ->assertOk()->assertJson(['result' => 'already']);

        $this->assertSame(1, AttendanceRecord::query()->count());
    }

    public function test_token_rejects_forged_stale_wrong_batch_and_closed(): void
    {
        $session = $this->openSession();
        $this->actingAs($this->student);

        $this->postJson(route('student.attendance.scan'), ['token' => 'garbage'])->assertStatus(422);
        $this->postJson(route('student.attendance.scan'), ['token' => $this->token($session).'x'])->assertStatus(422);

        $this->travel(40)->seconds();
        $this->postJson(route('student.attendance.scan'), ['token' => $this->staleToken($session)])->assertStatus(422);
        $this->travelBack();

        $outsider = User::factory()->student()->paid()->create();
        $this->actingAs($outsider)->postJson(route('student.attendance.scan'), ['token' => $this->token($session)])
            ->assertStatus(422);

        $this->actingAs($this->teacher)->post(route('teacher.attendance.close', $session));
        $this->actingAs($this->student)->postJson(route('student.attendance.scan'), ['token' => $this->token($session)])
            ->assertStatus(422);
    }

    private function staleToken(AttendanceSession $session): string
    {
        $this->travelBack();
        $token = $this->token($session);
        $this->travel(40)->seconds();

        return $token;
    }

    public function test_previous_slot_is_still_accepted_for_scan_delay(): void
    {
        $this->travelTo(now()->startOfMinute());
        $session = $this->openSession();
        $token = $this->token($session);

        $this->travel(16)->seconds();

        $this->actingAs($this->student)
            ->postJson(route('student.attendance.scan'), ['token' => $token])
            ->assertOk();
    }

    public function test_session_older_than_eight_hours_is_closed_automatically(): void
    {
        $session = AttendanceSession::factory()->create(['batch_id' => $this->batch->id, 'started_at' => now()->subHours(9)]);

        $this->assertFalse($session->isOpen());
        $this->assertNull(app(AttendanceTokenService::class)->verify($this->token($session)));
    }

    public function test_manual_changes_are_audited_and_can_excuse_or_remove(): void
    {
        $session = $this->openSession();
        $this->actingAs($this->teacher);

        $url = route('teacher.attendance.students.update', [$session, $this->student]);
        $this->put($url, ['status' => 'present'])->assertRedirect();
        $this->put($url, ['status' => 'excused', 'reason' => 'Sick leave'])->assertRedirect();
        $this->put($url, ['status' => 'absent', 'reason' => 'Left early'])->assertRedirect();

        $this->assertSame(0, AttendanceRecord::query()->count());
        $changes = AttendanceRecordChange::query()->orderBy('id')->get();
        $this->assertSame([[null, 'present'], ['present', 'excused'], ['excused', null]], $changes->map(fn ($c) => [$c->from_status, $c->to_status])->all());
        $this->assertSame($this->teacher->id, $changes[0]->changed_by);

        $this->put($url, ['status' => 'bogus'])->assertSessionHasErrors('status');
        $this->put(route('teacher.attendance.students.update', [$session, User::factory()->student()->create()]), ['status' => 'present'])->assertStatus(422);
    }

    public function test_summary_derives_absence_and_ignores_excused_in_the_percentage(): void
    {
        $service = app(AttendanceSummaryService::class);
        $sessions = AttendanceSession::factory()->closed()->count(4)->create(['batch_id' => $this->batch->id]);

        AttendanceRecord::create(['attendance_session_id' => $sessions[0]->id, 'student_id' => $this->student->id, 'status' => AttendanceStatus::Present, 'method' => 'qr', 'marked_at' => now()]);
        AttendanceRecord::create(['attendance_session_id' => $sessions[1]->id, 'student_id' => $this->student->id, 'status' => AttendanceStatus::Present, 'method' => 'qr', 'marked_at' => now()]);
        AttendanceRecord::create(['attendance_session_id' => $sessions[2]->id, 'student_id' => $this->student->id, 'status' => AttendanceStatus::Excused, 'method' => 'manual', 'marked_at' => now()]);

        $summary = $service->forStudent($this->student);

        $this->assertSame(4, $summary['total_sessions']);
        $this->assertSame(2, $summary['attended']);
        $this->assertSame(1, $summary['excused']);
        $this->assertSame(1, $summary['absent']);
        $this->assertSame(67, $summary['percentage']);

        $this->actingAs($this->student)->get(route('student.attendance.index'))
            ->assertInertia(fn (Assert $page) => $page->component('app/student/attendance')->where('summary.percentage', 67));
    }

    public function test_live_endpoint_returns_rotating_token_and_roster_as_json(): void
    {
        $session = $this->openSession();

        $first = $this->getJson(route('teacher.attendance.live', $session))->assertOk()->json();
        $this->assertArrayHasKey('token', $first['token']);
        $this->assertSame(1, $first['total']);

        $this->travel(20)->seconds();
        $second = $this->getJson(route('teacher.attendance.live', $session))->json();
        $this->assertNotSame($first['token']['token'], $second['token']['token']);
    }

    public function test_low_attendance_warning_is_sent_once_when_a_session_closes(): void
    {
        Notification::fake();

        // Four earlier sessions the student missed completely.
        AttendanceSession::factory()->closed()->count(4)->create(['batch_id' => $this->batch->id]);
        $session = $this->openSession();

        $this->post(route('teacher.attendance.close', $session));

        Notification::assertSentToTimes($this->student, LowAttendanceWarning::class, 1);
        $this->assertNotNull(BatchEnrollment::query()->where('student_id', $this->student->id)->value('attendance_alert_sent_at'));

        // A second close within the cooldown does not warn again.
        $again = AttendanceSession::factory()->create(['batch_id' => $this->batch->id]);
        $this->post(route('teacher.attendance.close', $again));
        Notification::assertSentToTimes($this->student, LowAttendanceWarning::class, 1);
    }

    public function test_no_warning_when_attendance_is_good_or_too_few_sessions(): void
    {
        Notification::fake();

        $session = $this->openSession();
        $this->post(route('teacher.attendance.close', $session));

        Notification::assertNothingSent();
    }

    public function test_attendance_summary_appears_on_the_student_dashboard_and_recruiter_bv_profile(): void
    {
        $sessions = AttendanceSession::factory()->closed()->count(2)->create(['batch_id' => $this->batch->id]);
        AttendanceRecord::create(['attendance_session_id' => $sessions[0]->id, 'student_id' => $this->student->id, 'status' => AttendanceStatus::Present, 'method' => 'qr', 'marked_at' => now()]);

        $this->actingAs($this->student)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('attendance.percentage', 50)->where('attendance.total_sessions', 2));

        $profile = StudentProfile::factory()->forPlacement()->create(['user_id' => $this->student->id]);
        $this->actingAs(User::factory()->recruiter()->create())
            ->get(route('recruiter.students.show', $profile))
            ->assertInertia(fn (Assert $page) => $page->where('language.attendance.percentage', 50)->where('language.attendance.attended', 1));
    }

    public function test_calendar_days_give_one_status_per_day(): void
    {
        BatchEnrollment::query()->update(['created_at' => now()->subDays(10)]);
        $today = now()->setTime(9, 0);
        AttendanceSession::factory()->create(['batch_id' => $this->batch->id, 'started_at' => $today, 'closed_at' => $today->copy()->addHour()]);
        $evening = AttendanceSession::factory()->create(['batch_id' => $this->batch->id, 'started_at' => $today->copy()->setTime(15, 0), 'closed_at' => $today->copy()->setTime(16, 0)]);
        $earlier = AttendanceSession::factory()->create(['batch_id' => $this->batch->id, 'started_at' => $today->copy()->subDay(), 'closed_at' => $today->copy()->subDay()->addHour()]);
        $excused = AttendanceSession::factory()->create(['batch_id' => $this->batch->id, 'started_at' => $today->copy()->subDays(2), 'closed_at' => $today->copy()->subDays(2)->addHour()]);

        AttendanceRecord::create(['attendance_session_id' => $evening->id, 'student_id' => $this->student->id, 'status' => AttendanceStatus::Present, 'method' => 'qr', 'marked_at' => now()]);
        AttendanceRecord::create(['attendance_session_id' => $excused->id, 'student_id' => $this->student->id, 'status' => AttendanceStatus::Excused, 'method' => 'manual', 'marked_at' => now()]);

        $days = collect(app(AttendanceSummaryService::class)->forStudent($this->student)['days'])->pluck('status', 'date');

        $this->assertSame('present', $days[$today->toDateString()]); // present at one of two classes that day
        $this->assertSame('absent', $days[$today->copy()->subDay()->toDateString()]);
        $this->assertSame('excused', $days[$today->copy()->subDays(2)->toDateString()]);
        $this->assertCount(3, $days);
    }
}
