<?php

namespace Tests\Feature\Filament;

use App\Enums\AttendanceStatus;
use App\Filament\Resources\AttendanceSessions\Pages\ListAttendanceSessions;
use App\Filament\Resources\AttendanceSessions\Pages\ViewAttendanceSession;
use App\Filament\Resources\AttendanceSessions\RelationManagers\RecordsRelationManager;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRecordChange;
use App\Models\AttendanceSession;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceSessionResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private AttendanceSession $session;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $batch = Batch::factory()->create();
        $this->student = User::factory()->student()->paid()->create(['name' => 'Pema Lepcha']);
        BatchEnrollment::factory()->create(['batch_id' => $batch->id, 'student_id' => $this->student->id]);
        $this->session = AttendanceSession::factory()->closed()->create(['batch_id' => $batch->id]);
    }

    public function test_admin_lists_and_views_sessions_with_absent_names(): void
    {
        Livewire::actingAs($this->admin)->test(ListAttendanceSessions::class)
            ->assertCanSeeTableRecords([$this->session]);

        Livewire::actingAs($this->admin)->test(ViewAttendanceSession::class, ['record' => $this->session->getKey()])
            ->assertSuccessful()
            ->assertSee('Pema Lepcha');
    }

    public function test_admin_marks_changes_and_removes_with_audit_trail(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(RecordsRelationManager::class, ['ownerRecord' => $this->session, 'pageClass' => ViewAttendanceSession::class]);

        $component->callTableAction('mark', data: ['student_id' => $this->student->id, 'status' => 'present', 'reason' => 'Late arrival']);
        $record = AttendanceRecord::query()->firstOrFail();
        $this->assertSame(AttendanceStatus::Present, $record->status);

        $component->callTableAction('changeStatus', $record, ['status' => 'excused', 'reason' => 'Medical']);
        $this->assertSame(AttendanceStatus::Excused, $record->fresh()->status);

        $component->callTableAction('markAbsent', $record->fresh(), ['reason' => 'Mistake']);
        $this->assertSame(0, AttendanceRecord::query()->count());
        $this->assertSame(3, AttendanceRecordChange::query()->where('changed_by', $this->admin->id)->count());
    }

    public function test_csv_exports_download_for_sessions_and_summary(): void
    {
        AttendanceRecord::create(['attendance_session_id' => $this->session->id, 'student_id' => $this->student->id, 'status' => AttendanceStatus::Present, 'method' => 'qr', 'marked_at' => now()]);

        Livewire::actingAs($this->admin)->test(ListAttendanceSessions::class)
            ->callAction('exportSummary', ['batch_id' => $this->session->batch_id])
            ->assertFileDownloaded('attendance-summary.csv');

        Livewire::actingAs($this->admin)->test(ListAttendanceSessions::class)
            ->callAction('exportSessions', ['batch_id' => $this->session->batch_id])
            ->assertFileDownloaded('attendance-sessions.csv');
    }
}
