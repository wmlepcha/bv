<?php

namespace Tests\Unit;

use App\Support\Brand;
use Tests\TestCase;

class BrandTrademarkTest extends TestCase
{
    public function test_trademarked_name_appends_mark(): void
    {
        $this->assertSame('Bilingual Vitae®', Brand::trademarkedName());
    }

    public function test_apply_trademark_is_idempotent(): void
    {
        $once = Brand::applyTrademark('Welcome to Bilingual Vitae today');
        $twice = Brand::applyTrademark($once);

        $this->assertSame('Welcome to Bilingual Vitae® today', $once);
        $this->assertSame($once, $twice);
    }

    public function test_apply_trademark_normalizes_tm_to_registered(): void
    {
        $this->assertSame(
            'Welcome to Bilingual Vitae® today',
            Brand::applyTrademark('Welcome to Bilingual Vitae™ today'),
        );
    }
}
