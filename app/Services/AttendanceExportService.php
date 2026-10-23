<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\BatchEnrollment;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceExportService
{
    public function __construct(
        private readonly AttendanceSummaryService $summaries,
        private readonly AttendanceService $attendance,
    ) {}

    /**
     * One row per student per session (absent is derived when there is no record).
     */
    public function sessions(?int $batchId, ?string $from, ?string $until): StreamedResponse
    {
        $sessions = AttendanceSession::query()
            ->with(['batch', 'opener'])
            ->when($batchId, fn ($q) => $q->where('batch_id', $batchId))
            ->when($from, fn ($q) => $q->where('started_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($until, fn ($q) => $q->where('started_at', '<=', Carbon::parse($until)->endOfDay()))
            ->orderBy('started_at')
            ->get();

        return $this->csv('attendance-sessions.csv', ['Date', 'Batch', 'Trainer', 'Candidate ID', 'Student', 'Email', 'Status', 'Marked via', 'Marked at'], function () use ($sessions): iterable {
            foreach ($sessions as $session) {
                foreach ($this->attendance->roster($session) as $row) {
                    yield [
                        $session->started_at->toDateString(),
                        $session->batch->name,
                        $session->opener?->name,
                        $row['student_number'],
                        $row['name'],
                        $row['email'],
                        $row['status'] ?? 'absent',
                        $row['method'],
                        $row['marked_at'],
                    ];
                }
            }
        });
    }

    /**
     * One row per currently enrolled student with their attendance percentage.
     */
    public function summary(?int $batchId): StreamedResponse
    {
        return $this->csv('attendance-summary.csv', ['Batch', 'Candidate ID', 'Student', 'Email', 'Classes', 'Present', 'Excused', 'Absent', 'Attendance %'], function () use ($batchId): iterable {
            $enrollments = BatchEnrollment::query()
                ->when($batchId, fn ($q) => $q->where('batch_id', $batchId))
                ->with(['student', 'batch'])
                ->get()
                ->sortBy(fn (BatchEnrollment $e): string => $e->batch->name.$e->student->name);

            foreach ($enrollments as $enrollment) {
                $summary = $this->summaries->forStudent($enrollment->student);

                yield [
                    $enrollment->batch->name,
                    $enrollment->student->student_number,
                    $enrollment->student->name,
                    $enrollment->student->email,
                    $summary['total_sessions'],
                    $summary['attended'],
                    $summary['excused'],
                    $summary['absent'],
                    $summary['percentage'],
                ];
            }
        });
    }

    /**
     * @param  list<string>  $header
     * @param  callable(): iterable<array<int, mixed>>  $rows
     */
    private function csv(string $filename, array $header, callable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);

            foreach ($rows() as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
