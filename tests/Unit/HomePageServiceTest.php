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

        HomeHighlight::query()->where('label', 'Integration')->update(['sort_order' => 0]);
        HomeHighlight::query()->where('label', 'Training')->update(['sort_order' => 99]);

        $payload = app(HomePageService::class)->toInertiaProps();

        $this->assertCount(4, $payload['hero']['stats']);
        $this->assertFalse(
            collect($payload['hero']['stats'])->contains('label', 'candidates certified'),
        );

        $this->assertSame('Integration', $payload['hero']['highlights'][0]['label']);
        $this->assertSame('Training', $payload['hero']['highlights'][4]['label']);
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
