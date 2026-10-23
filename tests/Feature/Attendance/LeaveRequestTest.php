<?php

namespace Tests\Feature\Attendance;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Filament\Resources\LeaveRequests\Pages\ListLeaveRequests;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveDecided;
use App\Notifications\LeaveRequested;
use App\Services\AttendanceService;
use App\Services\AttendanceSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
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
        BatchEnrollment::factory()->create(['batch_id' => $this->batch->id, 'student_id' => $this->student->id, 'created_at' => now()->subDays(20)]);
    }

    private function submit(array $overrides = []): void
    {
        $this->actingAs($this->student)->post(route('student.leave-requests.store'), array_merge([
            'from_date' => now()->addDay()->toDateString(),
            'to_date' => now()->addDays(2)->toDateString(),
            'reason' => 'Family function',
        ], $overrides));
    }

    public function test_student_submits_leave_and_trainer_is_notified(): void
    {
        Notification::fake();

        $this->submit();

        $leave = LeaveRequest::query()->sole();
        $this->assertSame(LeaveStatus::Pending, $leave->status);
        $this->assertSame($this->batch->id, $leave->batch_id);
        Notification::assertSentTo($this->teacher, LeaveRequested::class);
    }

    public function test_validation_rules_for_dates_and_overlap(): void
    {
        $this->actingAs($this->student);

        $this->submit(['to_date' => now()->toDateString(), 'from_date' => now()->addDays(3)->toDateString()]);
        $this->submit(['from_date' => now()->subDays(30)->toDateString(), 'to_date' => now()->subDays(29)->toDateString()]);
        $this->submit(['from_date' => now()->addDay()->toDateString(), 'to_date' => now()->addDays(60)->toDateString()]);
        $this->assertSame(0, LeaveRequest::query()->count());

        $this->submit();
        $this->submit(['from_date' => now()->addDays(2)->toDateString(), 'to_date' => now()->addDays(4)->toDateString()]);
        $this->assertSame(1, LeaveRequest::query()->count());

        $this->post(route('student.leave-requests.store'), ['reason' => ''])->assertSessionHasErrors(['from_date', 'to_date', 'reason']);
    }

    public function test_student_can_cancel_only_own_pending_requests(): void
    {
        $this->submit();
        $leave = LeaveRequest::query()->sole();

        $this->actingAs(User::factory()->student()->paid()->create())
            ->delete(route('student.leave-requests.destroy', $leave))->assertForbidden();

        $this->actingAs($this->student)->delete(route('student.leave-requests.destroy', $leave))->assertRedirect();
        $this->assertModelMissing($leave);
    }

    public function test_trainer_approves_and_student_is_notified_only_own_batches(): void
    {
        $this->submit();
        $leave = LeaveRequest::query()->sole();
        Notification::fake();

        $this->actingAs(User::factory()->teacher()->create())
            ->put(route('teacher.leave-requests.update', $leave), ['decision' => 'approve'])->assertNotFound();

        $this->actingAs($this->teacher)
            ->put(route('teacher.leave-requests.update', $leave), ['decision' => 'approve', 'note' => 'Get well soon'])->assertRedirect();

        $this->assertSame(LeaveStatus::Approved, $leave->fresh()->status);
        $this->assertSame($this->teacher->id, $leave->fresh()->decided_by);
        Notification::assertSentTo($this->student, LeaveDecided::class);

        $this->put(route('teacher.leave-requests.update', $leave), ['decision' => 'reject'])->assertStatus(422);
    }

    public function test_attendance_page_for_trainer_lists_leave_requests(): void
    {
        $this->submit();

        $this->actingAs($this->teacher)->get(route('teacher.attendance.index'))
            ->assertInertia(fn ($page) => $page->has('leaveRequests', 1)->where('leaveRequests.0.status', 'pending'));
    }

    public function test_approved_leave_marks_the_calendar_and_counts_sessions_as_leave(): void
    {
        $day = now()->subDays(3)->setTime(10, 0);
        $session = AttendanceSession::factory()->create(['batch_id' => $this->batch->id, 'started_at' => $day, 'closed_at' => $day->copy()->addHour()]);
        $other = AttendanceSession::factory()->create(['batch_id' => $this->batch->id, 'started_at' => $day->copy()->subDay(), 'closed_at' => $day->copy()->subDay()->addHour()]);
        AttendanceRecord::create(['attendance_session_id' => $other->id, 'student_id' => $this->student->id, 'status' => AttendanceStatus::Present, 'method' => 'qr', 'marked_at' => now()]);

        LeaveRequest::factory()->approved()->create([
            'student_id' => $this->student->id,
            'batch_id' => $this->batch->id,
            'from_date' => $day->toDateString(),
            'to_date' => now()->addDays(2)->toDateString(),
        ]);

        $summary = app(AttendanceSummaryService::class)->forStudent($this->student);
        $days = collect($summary['days'])->pluck('status', 'date');

        $this->assertSame(2, $summary['total_sessions']);
        $this->assertSame(1, $summary['excused']);
        $this->assertSame(100, $summary['percentage']);
        $this->assertSame('excused', $days[$day->toDateString()]);
        $this->assertSame('excused', $days[now()->addDay()->toDateString()]); // future leave day without a class
        $this->assertSame('present', $days[$day->copy()->subDay()->toDateString()]);

        $roster = app(AttendanceService::class)->roster($session);
        $this->assertSame('excused', $roster->firstWhere('student_id', $this->student->id)['status']);
        $this->assertSame('leave', $roster->firstWhere('student_id', $this->student->id)['method']);
    }

    public function test_pending_or_rejected_leave_does_not_count(): void
    {
        $day = now()->subDays(2)->setTime(10, 0);
        AttendanceSession::factory()->create(['batch_id' => $this->batch->id, 'started_at' => $day, 'closed_at' => $day->copy()->addHour()]);
        LeaveRequest::factory()->create(['student_id' => $this->student->id, 'batch_id' => $this->batch->id, 'from_date' => $day->toDateString(), 'to_date' => $day->toDateString()]);

        $summary = app(AttendanceSummaryService::class)->forStudent($this->student);

        $this->assertSame(0, $summary['excused']);
        $this->assertSame(1, $summary['absent']);
    }

    public function test_admin_decides_in_filament(): void
    {
        $this->submit();
        $leave = LeaveRequest::query()->sole();
        Notification::fake();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(ListLeaveRequests::class)
            ->assertCanSeeTableRecords([$leave])
            ->callTableAction('approve', $leave, ['note' => 'Ok']);

        $this->assertSame(LeaveStatus::Approved, $leave->fresh()->status);
    }
}
