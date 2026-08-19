<?php

namespace Tests\Unit;

use App\Models\ActivationCode;
use App\Services\ActivationCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivationCodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_matches_bv_26_prefix_and_four_character_suffix(): void
    {
        $code = app(ActivationCodeGenerator::class)->generate();

        $this->assertMatchesRegularExpression('/^BV-26-[A-Z2-9]{4}$/', $code);
    }

    public function test_generate_skips_existing_codes(): void
    {
        $generator = app(ActivationCodeGenerator::class);

        ActivationCode::create([
            'code' => 'BV-26-TEST',
            'student_number' => '003-2026-0099',
        ]);

        $code = $generator->generate();

        $this->assertNotSame('BV-26-TEST', $code);
        $this->assertMatchesRegularExpression('/^BV-26-[A-Z2-9]{4}$/', $code);
    }
}
