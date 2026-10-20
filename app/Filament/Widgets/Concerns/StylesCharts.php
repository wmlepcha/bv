<?php

namespace App\Filament\Widgets\Concerns;

use Filament\Support\RawJs;

/**
 * Shared look for the dashboard charts.
 */
trait StylesCharts
{
    protected const WARM = '#FC5C2E';

    protected const COOL = '#2A80CC';

    /**
     * Column chart like the reference: hatched pale columns with the tallest one solid.
     */
    protected function hatchedBars(string $color, ?int $max = null): RawJs
    {
        $yMax = $max === null ? '' : ", max: {$max}";

        return RawJs::make(str_replace('COLOR', $color, <<<JS
            {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, border: { display: false } },
                    y: { beginAtZero: true, border: { display: false }, grid: { borderDash: [4, 6] }{$yMax},
                         ticks: { maxTicksLimit: 5, callback: (v) => (v >= 1000 ? v / 1000 + 'k' : v) } },
                },
                datasets: { bar: {
                    borderRadius: 8, borderSkipped: false, maxBarThickness: 34,
                    backgroundColor: (ctx) => {
                        const d = ctx.chart.data.datasets[0].data;
                        if (ctx.dataIndex === d.indexOf(Math.max(...d))) { return 'COLOR'; }
                        const c = document.createElement('canvas'); c.width = c.height = 8;
                        const g = c.getContext('2d'); g.strokeStyle = 'COLOR66'; g.lineWidth = 1.5;
                        g.beginPath(); g.moveTo(-1, 9); g.lineTo(9, -1); g.stroke();
                        return ctx.chart.ctx.createPattern(c, 'repeat');
                    },
                } },
            }
            JS));
    }
}
