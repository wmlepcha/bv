<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhoneNumberTest extends TestCase
{
    #[DataProvider('validPhones')]
    public function test_valid_phones(string $input, string $expectedNormalized): void
    {
        $this->assertTrue(PhoneNumber::isValid($input));
        $this->assertSame($expectedNormalized, PhoneNumber::normalize($input));
    }

    #[DataProvider('invalidPhones')]
    public function test_invalid_phones(?string $input): void
    {
        $this->assertFalse(PhoneNumber::isValid($input));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function validPhones(): array
    {
        return [
            'india e164' => ['+919832581198', '+919832581198'],
            'with spaces' => ['+91 98325 81198', '+919832581198'],
            'with dashes' => ['+91-98325-81198', '+919832581198'],
            'double zero prefix' => ['00919832581198', '+919832581198'],
            'uk' => ['+442071838750', '+442071838750'],
        ];
    }

    /**
     * @return array<string, array{0: string|null}>
     */
    public static function invalidPhones(): array
    {
        return [
            'missing plus' => ['9832581198'],
            'leading zero local' => ['09832581198'],
            'junk' => ['not-a-phone'],
            'plus only' => ['+'],
            'too short' => ['+123'],
            'empty' => [''],
            'null' => [null],
        ];
    }
}
