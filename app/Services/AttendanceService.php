<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Exceptions\AttendanceException;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRecordChange;
use App\Models\AttendanceSession;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttendanceService
{
    public function __construct(
        private readonly AttendanceTokenService $tokens,
        private readonly AttendanceAlertService $alerts,
    ) {}

    /**
     * Start a session for the batch, or return the one already open.
     */
    public function open(Batch $batch, User $by): AttendanceSession
    {
        $existing = AttendanceSession::query()
            ->where('batch_id', $batch->id)
            ->whereNull('closed_at')
            ->latest('started_at')
            ->get()
            ->first(fn (AttendanceSession $session): bool => $session->isOpen());

        return $existing ?? AttendanceSession::query()->create([
            'batch_id' => $batch->id,
            'opened_by' => $by->id,
            'secret' => Str::random(40),
            'started_at' => now(),
        ]);
    }

    public function close(AttendanceSession $session): void
    {
        if ($session->closed_at === null) {
            $session->update(['closed_at' => now()]);
            $this->alerts->warnLowAttendance($session->batch_id);
        }
    }

    /**
     * Student scans the rotating code. Idempotent: scanning twice keeps the first mark.
     *
     * @return 'marked'|'already'
     *
     * @throws AttendanceException
     */
    public function markByToken(User $student, string $token): string
    {
        $session = $this->tokens->verify($token);

        if ($session === null) {
            throw new AttendanceException('This code has expired. Ask your trainer to show the QR again and scan the new one.');
        }

        if (! $student->hasActiveRegistration()) {
            throw new AttendanceException('Your account is not active yet.');
        }

        $enrolled = BatchEnrollment::query()
            ->where('student_id', $student->id)
            ->where('batch_id', $session->batch_id)
            ->exists();

        if (! $enrolled) {
            throw new AttendanceException('You are not enrolled in this batch.');
        }

        $record = AttendanceRecord::query()->firstOrCreate(
            ['attendance_session_id' => $session->id, 'student_id' => $student->id],
            ['status' => AttendanceStatus::Present, 'method' => 'qr', 'marked_at' => now()],
        );

        return $record->wasRecentlyCreated ? 'marked' : 'already';
    }

    /**
     * Manual correction by a trainer/admin. A null status removes the record (the student counts as absent).
     * Every change is written to the audit table.
     *
     * @throws AttendanceException
     */
    public function setStatus(AttendanceSession $session, User $student, ?AttendanceStatus $status, User $by, ?string $reason = null): void
    {
        $enrolled = BatchEnrollment::query()
            ->where('student_id', $student->id)
            ->where('batch_id', $session->batch_id)
            ->exists();

        if (! $enrolled) {
            throw new AttendanceException('This student is not in the session\'s batch.');
        }

        DB::transaction(function () use ($session, $student, $status, $by, $reason): void {
            $record = AttendanceRecord::query()
                ->where('attendance_session_id', $session->id)
                ->where('student_id', $student->id)
                ->first();

            $from = $record?->status;

            if ($from === $status) {
                return;
            }

            if ($status === null) {
                $record?->delete();
            } else {
                AttendanceRecord::query()->updateOrCreate(
                    ['attendance_session_id' => $session->id, 'student_id' => $student->id],
                    ['status' => $status, 'method' => 'manual', 'marked_at' => now(), 'marked_by' => $by->id, 'note' => $reason],
                );
            }

            AttendanceRecordChange::query()->create([
                'attendance_session_id' => $session->id,
                'student_id' => $student->id,
                'changed_by' => $by->id,
                'from_status' => $from?->value,
                'to_status' => $status?->value,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * Everyone enrolled in the batch with their status for this session (null = absent).
     * Students with an approved leave covering the session date show as Leave (method "leave").
     *
     * @return Collection<int, array{student_id: int, name: string, student_number: string|null, email: string, status: string|null, marked_at: string|null, method: string|null}>
     */
    public function roster(AttendanceSession $session): Collection
    {
        $records = $session->records()->get()->keyBy('student_id');
        $date = $session->started_at->toDateString();

        $onLeave = LeaveRequest::query()
            ->where('batch_id', $session->batch_id)
            ->where('status', LeaveStatus::Approved)
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->pluck('student_id')
            ->all();

        return BatchEnrollment::query()
            ->where('batch_id', $session->batch_id)
            ->with('student:id,name,email,student_number')
            ->get()
            ->map(function (BatchEnrollment $enrollment) use ($records, $onLeave): array {
                $record = $records->get($enrollment->student_id);
                $leave = $record === null && in_array($enrollment->student_id, $onLeave, true);

                return [
                    'student_id' => $enrollment->student_id,
                    'name' => $enrollment->student->name,
                    'student_number' => $enrollment->student->student_number,
                    'email' => $enrollment->student->email,
                    'status' => $leave ? AttendanceStatus::Excused->value : $record?->status->value,
                    'marked_at' => $record?->marked_at?->toIso8601String(),
                    'method' => $leave ? 'leave' : $record?->method,
                ];
            })
            ->sortBy('name')
            ->values();
    }
}
