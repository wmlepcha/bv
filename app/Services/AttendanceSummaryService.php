<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\BatchEnrollment;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Collection;

class AttendanceSummaryService
{
    public function __construct(private readonly LeaveService $leaves) {}

    /**
     * Attendance for the student's current batch, counting closed sessions since they joined it.
     * Excused sessions are removed from the percentage.
     *
     * @return array{
     *     total_sessions: int, attended: int, excused: int, absent: int,
     *     percentage: int|null,
     *     recent: list<array{date: string, status: string}>,
     *     days: list<array{date: string, status: string}>
     * }
     */
    public function forStudent(User $student): array
    {
        $enrollment = BatchEnrollment::query()->where('student_id', $student->id)->first();

        if ($enrollment === null) {
            return $this->empty();
        }

        $since = $enrollment->transferred_at ?? $enrollment->created_at;

        $sessions = AttendanceSession::query()
            ->where('batch_id', $enrollment->batch_id)
            ->whereNotNull('closed_at')
            ->where('started_at', '>=', $since->copy()->startOfDay())
            ->orderByDesc('started_at')
            ->get();

        $records = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereIn('attendance_session_id', $sessions->pluck('id'))
            ->get()
            ->keyBy('attendance_session_id');

        $leaves = $this->leaves->approvedFor($student);

        // Status per session: the recorded one, else Leave when an approved leave covers that date, else absent.
        $statuses = $sessions->mapWithKeys(fn (AttendanceSession $session): array => [
            $session->id => $records->get($session->id)?->status->value
                ?? ($leaves->contains(fn (LeaveRequest $leave): bool => $leave->covers($session->started_at->toDateString()))
                    ? AttendanceStatus::Excused->value
                    : 'absent'),
        ]);

        $attended = $statuses->filter(fn (string $status): bool => $status === AttendanceStatus::Present->value)->count();
        $excused = $statuses->filter(fn (string $status): bool => $status === AttendanceStatus::Excused->value)->count();
        $total = $sessions->count();
        $counted = $total - $excused;

        return [
            'total_sessions' => $total,
            'attended' => $attended,
            'excused' => $excused,
            'absent' => max(0, $total - $attended - $excused),
            'percentage' => $counted > 0 ? (int) round($attended / $counted * 100) : null,
            'recent' => $sessions->take(10)->map(fn (AttendanceSession $session): array => [
                'date' => $session->started_at->toDateString(),
                'status' => $statuses[$session->id],
            ])->values()->all(),
            'days' => $this->days($sessions, $statuses, $leaves),
        ];
    }

    /**
     * One status per calendar day: present if they attended any class that day, else leave, else absent.
     * Approved leave days are marked even when no class was held.
     *
     * @param  Collection<int, AttendanceSession>  $sessions
     * @param  Collection<int, string>  $statuses  session id => present|excused|absent
     * @param  Collection<int, LeaveRequest>  $leaves
     * @return list<array{date: string, status: string}>
     */
    private function days(Collection $sessions, Collection $statuses, Collection $leaves): array
    {
        $days = $sessions
            ->groupBy(fn (AttendanceSession $session): string => $session->started_at->toDateString())
            ->map(fn (Collection $day): string => match (true) {
                $day->contains(fn (AttendanceSession $session): bool => $statuses[$session->id] === AttendanceStatus::Present->value) => 'present',
                $day->contains(fn (AttendanceSession $session): bool => $statuses[$session->id] === AttendanceStatus::Excused->value) => 'excused',
                default => 'absent',
            });

        foreach ($leaves as $leave) {
            for ($date = $leave->from_date; $date->lte($leave->to_date); $date = $date->addDay()) {
                $key = $date->toDateString();

                if (($days[$key] ?? null) !== 'present') {
                    $days[$key] = 'excused';
                }
            }
        }

        return $days->map(fn (string $status, string $date): array => ['date' => $date, 'status' => $status])
            ->sortBy('date')
            ->values()
            ->all();
    }

    /**
     * @return array{total_sessions: int, attended: int, excused: int, absent: int, percentage: int|null, recent: list<array{date: string, status: string}>, days: list<array{date: string, status: string}>}
     */
    private function empty(): array
    {
        return ['total_sessions' => 0, 'attended' => 0, 'excused' => 0, 'absent' => 0, 'percentage' => null, 'recent' => [], 'days' => []];
    }
}
