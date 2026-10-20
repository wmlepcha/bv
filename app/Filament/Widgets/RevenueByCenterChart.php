<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\StylesCharts;
use App\Filament\Widgets\Concerns\UsesDashboardRange;
use App\Models\Payment;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RevenueByCenterChart extends ChartWidget
{
    use StylesCharts;
    use UsesDashboardRange;

    protected ?string $heading = 'Collected by center';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = ['default' => 1, 'lg' => 4];

    protected ?string $maxHeight = '200px';

    protected function getData(): array
    {
        $rows = $this->inRange(Payment::query()->collected(), $this->paidAtColumn())
            ->leftJoin('centers', 'centers.id', '=', 'payments.center_id')
            ->groupBy('label')
            ->orderByDesc('total')
            ->toBase()
            ->get([DB::raw("COALESCE(centers.name, 'Unassigned') as label"), DB::raw('SUM(payments.amount) as total')]);

        $totals = $rows->map(fn (object $row): float => $row->total / 100)->all();

        return [
            'datasets' => [[
                'label' => 'Collected (₹)',
                'data' => $totals,
            ]],
            'labels' => $rows->pluck('label')->all(),
        ];
    }

    protected function getOptions(): RawJs
    {
        return $this->hatchedBars(self::WARM);
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
