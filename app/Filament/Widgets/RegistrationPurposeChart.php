<?php

namespace App\Filament\Widgets;

use App\Enums\RegistrationPurpose;
use App\Filament\Widgets\Concerns\StylesCharts;
use App\Filament\Widgets\Concerns\UsesDashboardRange;
use App\Models\StudentProfile;
use Filament\Widgets\ChartWidget;

class RegistrationPurposeChart extends ChartWidget
{
    use StylesCharts;
    use UsesDashboardRange;

    protected ?string $heading = 'Training vs Training + Placement';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $counts = $this->inRange(StudentProfile::query(), 'created_at')
            ->selectRaw('registration_purpose, COUNT(*) as total')
            ->groupBy('registration_purpose')
            ->pluck('total', 'registration_purpose');

        $purposes = RegistrationPurpose::cases();

        return [
            'datasets' => [[
                'data' => array_map(fn (RegistrationPurpose $purpose): int => (int) ($counts[$purpose->value] ?? 0), $purposes),
                'backgroundColor' => [self::COOL, self::WARM],
                'borderWidth' => 0,
                'borderRadius' => 10,
                'spacing' => 4,
                'hoverOffset' => 6,
            ]],
            'labels' => array_map(fn (RegistrationPurpose $purpose): string => $purpose->label(), $purposes),
        ];
    }

    protected int|string|array $columnSpan = ['default' => 1, 'lg' => 4];

    protected ?string $maxHeight = '200px';

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'cutout' => '72%',
            'scales' => ['x' => ['display' => false], 'y' => ['display' => false]],
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'padding' => 12]]],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
