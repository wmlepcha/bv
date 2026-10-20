<?php

namespace App\Filament\Widgets;

use App\Enums\AttendanceStatus;
use App\Filament\Widgets\Concerns\StylesCharts;
use App\Filament\Widgets\Concerns\UsesDashboardRange;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class AttendanceByBatchChart extends ChartWidget
{
    use StylesCharts;
    use UsesDashboardRange;

    protected ?string $heading = 'Attendance by batch (%)';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = ['default' => 1, 'lg' => 5];

    protected ?string $maxHeight = '220px';

    protected function getData(): array
    {
        $sessions = $this->inRange(AttendanceSession::query(), 'started_at')
            ->selectRaw('batch_id, COUNT(*) as total')->groupBy('batch_id')->pluck('total', 'batch_id');

        $present = $this->inRange(AttendanceRecord::query()->where('status', AttendanceStatus::Present), 'attendance_records.marked_at')
            ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendance_records.attendance_session_id')
            ->selectRaw('attendance_sessions.batch_id, COUNT(*) as total')->groupBy('attendance_sessions.batch_id')->pluck('total', 'batch_id');

        $enrolled = BatchEnrollment::query()->selectRaw('batch_id, COUNT(*) as total')->groupBy('batch_id')->pluck('total', 'batch_id');

        $batches = Batch::query()->where('is_active', true)->whereIn('id', $sessions->keys())->orderBy('name')->get(['id', 'name']);

        $rates = $batches->map(function (Batch $batch) use ($sessions, $present, $enrolled): float {
            $expected = $sessions[$batch->id] * ($enrolled[$batch->id] ?? 0);

            return $expected > 0 ? round(($present[$batch->id] ?? 0) / $expected * 100, 1) : 0;
        })->all();

        return [
            'datasets' => [[
                'label' => 'Present %',
                'data' => $rates,
            ]],
            'labels' => $batches->pluck('name')->all(),
        ];
    }

    protected function getOptions(): RawJs
    {
        return $this->hatchedBars(self::COOL, 100);
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
