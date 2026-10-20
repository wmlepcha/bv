<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FormatsMoney;
use App\Filament\Widgets\Concerns\UsesDashboardRange;
use App\Models\Center;
use App\Models\Payment;
use Filament\Widgets\Widget;

class CenterCollections extends Widget
{
    use FormatsMoney;
    use UsesDashboardRange;

    protected string $view = 'filament.widgets.center-collections';

    protected int|string|array $columnSpan = ['default' => 1, 'lg' => 4];

    /**
     * @return array{total: int, rows: array<int, array{name: string, amount: int, payments: int, color: string}>}
     */
    protected function getViewData(): array
    {
        $palette = ['#FC5C2E', '#2A80CC', '#16a34a', '#f59e0b', '#8b5cf6', '#06b6d4'];

        $totals = $this->inRange(Payment::query()->collected(), $this->paidAtColumn())
            ->selectRaw('center_id, SUM(amount) as total, COUNT(*) as payments')->groupBy('center_id')->toBase()->get()->keyBy('center_id');

        $rows = Center::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
            ->values()
            ->map(fn (Center $center, int $index): array => [
                'name' => $center->name,
                'amount' => (int) ($totals->get($center->id)->total ?? 0),
                'payments' => (int) ($totals->get($center->id)->payments ?? 0),
                'color' => $palette[$index % count($palette)],
            ])
            ->all();

        $unassigned = $totals->first(fn (object $row): bool => $row->center_id === null);

        if ($unassigned !== null) {
            $rows[] = ['name' => 'Unassigned', 'amount' => (int) $unassigned->total, 'payments' => (int) $unassigned->payments, 'color' => '#9ca3af'];
        }

        return ['total' => array_sum(array_column($rows, 'amount')), 'rows' => $rows];
    }
}
