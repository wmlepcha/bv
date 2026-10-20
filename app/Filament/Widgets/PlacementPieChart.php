<?php

namespace App\Filament\Widgets;

use App\Enums\PlacementStatus;
use App\Filament\Widgets\Concerns\StylesCharts;
use App\Models\StudentProfile;
use Filament\Widgets\ChartWidget;

class PlacementPieChart extends ChartWidget
{
    use StylesCharts;

    protected ?string $heading = 'Placed vs Unplaced';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $candidates = StudentProfile::query()->registeredForPlacement();
        $placed = (clone $candidates)->where('placement_status', PlacementStatus::Hired)->count();
        $unplaced = $candidates->count() - $placed;
        $total = max($placed + $unplaced, 1);

        $label = fn (string $name, int $count): string => "{$name} · {$count} (".round($count / $total * 100).'%)';

        return [
            'datasets' => [[
                'data' => [$placed, $unplaced],
                'backgroundColor' => ['#16a34a', '#f59e0b'],
                'borderWidth' => 4,
                'borderColor' => '#ffffff',
                'hoverOffset' => 8,
            ]],
            'labels' => [$label('Placed', $placed), $label('Unplaced', $unplaced)],
        ];
    }

    protected int|string|array $columnSpan = ['default' => 1, 'lg' => 4];

    protected ?string $maxHeight = '200px';

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'scales' => ['x' => ['display' => false], 'y' => ['display' => false]],
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'padding' => 12]]],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
