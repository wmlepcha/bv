<?php

namespace Tests\Unit;

use App\Models\HomeHighlight;
use App\Models\HomeStat;
use App\Services\HomePageService;
use Database\Seeders\HomePageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomePageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payload_filters_hidden_items_and_respects_sort_order(): void
    {
        $this->seed(HomePageSeeder::class);

        HomeStat::query()->where('label', 'candidates certified')->update(['is_visible' => false]);

        HomeHighlight::query()
            ->where('label', 'Software suite – for Government Agencies')
            ->update(['sort_order' => 0]);
        HomeHighlight::query()
            ->where('label', 'Digital products for candidates')
            ->update(['sort_order' => 99]);

        $payload = app(HomePageService::class)->toInertiaProps();

        $this->assertCount(4, $payload['hero']['stats']);
        $this->assertFalse(
            collect($payload['hero']['stats'])->contains('label', 'candidates certified'),
        );

        $this->assertCount(2, $payload['hero']['highlightColumns']);
        $this->assertSame('Software suite', $payload['hero']['highlightColumns'][0]['heading']);
        $this->assertSame('Services', $payload['hero']['highlightColumns'][1]['heading']);
        $this->assertSame(
            'Software suite – for Government Agencies',
            $payload['hero']['highlightColumns'][0]['items'][0]['label'],
        );
        $this->assertSame(
            'Digital products for candidates',
            $payload['hero']['highlightColumns'][0]['items'][2]['label'],
        );
        $this->assertCount(3, $payload['hero']['highlightColumns'][0]['items']);
        $this->assertCount(2, $payload['hero']['highlightColumns'][1]['items']);
    }

    public function test_cache_is_cleared_when_stat_is_updated(): void
    {
        $this->seed(HomePageSeeder::class);

        app(HomePageService::class)->toInertiaProps();
        $this->assertTrue(Cache::has(HomePageService::CACHE_KEY));

        HomeStat::query()->first()?->update(['value' => 999]);

        $this->assertFalse(Cache::has(HomePageService::CACHE_KEY));
    }
}
