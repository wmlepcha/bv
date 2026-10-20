<x-filament-widgets::widget>
    <div class="bv-kpis">
        @foreach ($cards as $card)
            <div class="bv-card bv-kpi">
                <div class="bv-kpi-head">
                    <span class="bv-kpi-label">{{ $card['label'] }}</span>
                    <span class="bv-icon bv-tint-{{ $card['tint'] }}">
                        <x-filament::icon :icon="$card['icon']" class="h-4 w-4" />
                    </span>
                </div>
                <div class="bv-kpi-value">{{ $card['value'] }}</div>
                <div class="bv-kpi-foot">{{ $card['footer'] }}</div>
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>
