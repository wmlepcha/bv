<?php

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Exceptions\AttendanceException;
use App\Models\BatchEnrollment;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveDecided;
use App\Notifications\LeaveRequested;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LeaveService
{
    public const MAX_DAYS = 31;

    public const MAX_BACKDATE_DAYS = 7;

    /**
     * @throws AttendanceException
     */
    public function submit(User $student, string $from, string $to, string $reason): LeaveRequest
    {
        $enrollment = BatchEnrollment::query()->where('student_id', $student->id)->with('batch.teacher')->first();

        if ($enrollment === null) {
            throw new AttendanceException('You are not enrolled in a batch yet.');
        }

        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();

        if ($end->lt($start)) {
            throw new AttendanceException('The end date cannot be before the start date.');
        }

        if ($start->lt(now()->startOfDay()->subDays(self::MAX_BACKDATE_DAYS))) {
            throw new AttendanceException('Leave can only be requested for the last '.self::MAX_BACKDATE_DAYS.' days or later.');
        }

        if ($start->diffInDays($end) >= self::MAX_DAYS) {
            throw new AttendanceException('A single leave request can cover at most '.self::MAX_DAYS.' days.');
        }

        $overlaps = LeaveRequest::query()
            ->where('student_id', $student->id)
            ->whereIn('status', [LeaveStatus::Pending, LeaveStatus::Approved])
            ->whereDate('from_date', '<=', $end)
            ->whereDate('to_date', '>=', $start)
            ->exists();

        if ($overlaps) {
            throw new AttendanceException('You already have a pending or approved leave request on these dates.');
        }

        $leave = LeaveRequest::query()->create([
            'student_id' => $student->id,
            'batch_id' => $enrollment->batch_id,
            'from_date' => $start,
            'to_date' => $end,
            'reason' => $reason,
        ]);

        $enrollment->batch->teacher?->notify(new LeaveRequested($leave));

        return $leave;
    }

    /**
     * @throws AttendanceException
     */
    public function decide(LeaveRequest $leave, User $by, bool $approve, ?string $note = null): void
    {
        if (! $leave->isPending()) {
            throw new AttendanceException('This request has already been decided.');
        }

        $leave->update([
            'status' => $approve ? LeaveStatus::Approved : LeaveStatus::Rejected,
            'decided_by' => $by->id,
            'decided_at' => now(),
            'decision_note' => $note,
        ]);

        $leave->student->notify(new LeaveDecided($leave));
    }

    /**
     * @throws AttendanceException
     */
    public function cancel(User $student, LeaveRequest $leave): void
    {
        if ($leave->student_id !== $student->id || ! $leave->isPending()) {
            throw new AttendanceException('Only your own pending requests can be cancelled.');
        }

        $leave->delete();
    }

    /**
     * @return Collection<int, LeaveRequest>
     */
    public function approvedFor(User $student): Collection
    {
        return LeaveRequest::query()
            ->where('student_id', $student->id)
            ->where('status', LeaveStatus::Approved)
            ->get();
    }
}
