<x-filament-widgets::widget>
    <div class="bv-card bv-centers">
        <div class="bv-card-title">Collected by center</div>
        <div class="bv-kpi-value">{{ $this->bigMoney($total) }}</div>
        <div class="bv-seg">
            @foreach ($rows as $row)
                @if ($row['amount'] > 0)
                    <span style="flex: {{ $row['amount'] }}; background: {{ $row['color'] }}" title="{{ $row['name'] }}"></span>
                @endif
            @endforeach
            @if ($total === 0)
                <span style="flex: 1; background: repeating-linear-gradient(135deg, #e5e7eb 0 4px, transparent 4px 8px)"></span>
            @endif
        </div>
        <ul class="bv-legend">
            @foreach ($rows as $row)
                <li>
                    <span class="bv-dot" style="background: {{ $row['color'] }}"></span>
                    <span class="bv-legend-name">{{ $row['name'] }}<small>{{ $row['payments'] }} {{ \Illuminate\Support\Str::plural('payment', $row['payments']) }}</small></span>
                    <strong>{{ $this->money($row['amount']) }}</strong>
                </li>
            @endforeach
        </ul>
    </div>
</x-filament-widgets::widget>
