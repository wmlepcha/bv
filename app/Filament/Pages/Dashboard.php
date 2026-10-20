<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AttendanceByBatchChart;
use App\Filament\Widgets\CenterCollections;
use App\Filament\Widgets\CollectedOverTimeChart;
use App\Filament\Widgets\DashboardStats;
use App\Filament\Widgets\PlacementPieChart;
use App\Filament\Widgets\RecentPaymentsTable;
use App\Filament\Widgets\RegistrationPurposeChart;
use App\Filament\Widgets\RevenueByCenterChart;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;

class Dashboard extends BaseDashboard
{
    use HasFiltersAction;

    private const PERIODS = ['month' => 'This month', '30d' => 'Last 30 days', 'year' => 'This year', 'all' => 'All time'];

    public function getHeading(): string
    {
        $hour = now()->hour;

        return ($hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening')).', '.explode(' ', (string) auth()->user()?->name)[0];
    }

    public function getSubheading(): string
    {
        return 'Here is how Bilingual Vitae is doing.';
    }

    public function setPeriod(string $period): void
    {
        $this->filters = ['range' => array_key_exists($period, self::PERIODS) ? $period : 'month'];
        $this->updatedFilters();
    }

    /**
     * @return array<int, ActionGroup>
     */
    protected function getHeaderActions(): array
    {
        $current = $this->filters['range'] ?? 'month';

        return [
            ActionGroup::make(array_map(
                fn (string $period, string $label): Action => Action::make("period_{$period}")
                    ->label($label)
                    ->icon($period === $current ? 'heroicon-m-check' : null)
                    ->action(fn () => $this->setPeriod($period)),
                array_keys(self::PERIODS),
                self::PERIODS,
            ))
                ->label(self::PERIODS[$current] ?? 'This month')
                ->icon('heroicon-m-calendar-days')
                ->color('gray')
                ->button()
                ->size('sm'),
        ];
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            DashboardStats::class,
            CollectedOverTimeChart::class,
            CenterCollections::class,
            RevenueByCenterChart::class,
            PlacementPieChart::class,
            RegistrationPurposeChart::class,
            AttendanceByBatchChart::class,
            RecentPaymentsTable::class,
        ];
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'lg' => 12];
    }
}
