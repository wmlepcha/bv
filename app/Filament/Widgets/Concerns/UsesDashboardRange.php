<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\CarbonImmutable;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Applies the dashboard's single date-range filter to a widget's query.
 */
trait UsesDashboardRange
{
    use InteractsWithPageFilters;

    /**
     * @return array{0: CarbonImmutable|null, 1: CarbonImmutable|null}
     */
    protected function dashboardRange(): array
    {
        return match ($this->pageFilters['range'] ?? 'month') {
            'all' => [null, null],
            '30d' => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
            'year' => [now()->startOfYear(), now()->endOfDay()],
            default => [now()->startOfMonth(), now()->endOfDay()],
        };
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function inRange(Builder $query, string|Expression $column): Builder
    {
        [$from, $to] = $this->dashboardRange();

        return $query
            ->when($from, fn (Builder $q) => $q->where($column, '>=', $from))
            ->when($to, fn (Builder $q) => $q->where($column, '<=', $to));
    }

    protected function isLongRange(): bool
    {
        return in_array($this->pageFilters['range'] ?? 'month', ['year', 'all'], true);
    }

    /**
     * Legacy payments can be paid without a paid_at, so fall back to when the row was created.
     */
    protected function paidAtColumn(): Expression
    {
        return DB::raw('COALESCE(payments.paid_at, payments.created_at)');
    }
}
