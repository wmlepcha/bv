<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FormatsMoney;
use App\Filament\Widgets\Concerns\StylesCharts;
use App\Filament\Widgets\Concerns\UsesDashboardRange;
use App\Models\Payment;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class CollectedOverTimeChart extends ChartWidget
{
    use FormatsMoney;
    use StylesCharts;
    use UsesDashboardRange;

    protected ?string $heading = 'Collected over time';

    public function getDescription(): ?string
    {
        return $this->money((int) $this->inRange(Payment::query()->collected(), $this->paidAtColumn())->sum('amount')).' in this period';
    }

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = ['default' => 1, 'lg' => 8];

    protected ?string $maxHeight = '230px';

    protected function getData(): array
    {
        $format = $this->isLongRange() ? 'M Y' : 'd M';
        $key = $this->isLongRange() ? 'Y-m' : 'Y-m-d';

        // ponytail: grouped in PHP to stay DB-agnostic; move to SQL if "All time" gets slow.
        $totals = $this->inRange(Payment::query()->collected(), $this->paidAtColumn())
            ->orderBy($this->paidAtColumn())
            ->get(['paid_at', 'created_at', 'amount'])
            ->groupBy(fn (Payment $payment): string => ($payment->paid_at ?? $payment->created_at)->format($key))
            ->map(fn ($group): array => [
                'label' => ($group->first()->paid_at ?? $group->first()->created_at)->format($format),
                'total' => $group->sum('amount') / 100,
            ])
            ->values();

        return [
            'datasets' => [[
                'label' => 'Collected (₹)',
                'data' => $totals->pluck('total')->all(),
            ]],
            'labels' => $totals->pluck('label')->all(),
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
