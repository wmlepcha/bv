<?php

namespace App\Services;

use App\Models\BatchEnrollment;
use App\Notifications\LowAttendanceWarning;

class AttendanceAlertService
{
    public function __construct(private readonly AttendanceSummaryService $summaries) {}

    /**
     * Warn students of a batch whose attendance is below the threshold, at most once per cooldown.
     *
     * @return int number of students warned
     */
    public function warnLowAttendance(int $batchId): int
    {
        $threshold = (int) config('attendance.low_threshold');
        $minSessions = (int) config('attendance.min_sessions_for_alert');
        $cooldown = (int) config('attendance.alert_cooldown_days');
        $warned = 0;

        BatchEnrollment::query()->where('batch_id', $batchId)->with('student')->get()
            ->each(function (BatchEnrollment $enrollment) use ($threshold, $minSessions, $cooldown, &$warned): void {
                $summary = $this->summaries->forStudent($enrollment->student);

                if (
                    $summary['total_sessions'] < $minSessions
                    || $summary['percentage'] === null
                    || $summary['percentage'] >= $threshold
                    || ($enrollment->attendance_alert_sent_at !== null && $enrollment->attendance_alert_sent_at->gt(now()->subDays($cooldown)))
                ) {
                    return;
                }

                $enrollment->student->notify(new LowAttendanceWarning($summary['percentage'], $threshold));
                $enrollment->forceFill(['attendance_alert_sent_at' => now()])->save();
                $warned++;
            });

        return $warned;
    }
}
