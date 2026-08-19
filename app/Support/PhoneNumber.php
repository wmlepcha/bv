<?php

namespace App\Support;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class PhoneNumber
{
    /**
     * After normalize: + then 8–15 digits total (country code starts 1–9).
     */
    public const PATTERN = '/^\+[1-9]\d{7,14}$/';

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        $normalized = preg_replace('/[\s\-()]/', '', $trimmed) ?? '';

        if (str_starts_with($normalized, '00')) {
            $normalized = '+'.substr($normalized, 2);
        }

        return $normalized === '' ? null : $normalized;
    }

    public static function isValid(?string $value): bool
    {
        $normalized = self::normalize($value);

        if ($normalized === null) {
            return false;
        }

        return preg_match(self::PATTERN, $normalized) === 1;
    }

    /**
     * Laravel rule: empty/null passes (use with nullable/required separately).
     */
    public static function rule(): ValidationRule
    {
        return new class implements ValidationRule
        {
            public function validate(string $attribute, mixed $value, Closure $fail): void
            {
                if ($value === null || $value === '') {
                    return;
                }

                if (! is_string($value) || ! PhoneNumber::isValid($value)) {
                    $fail('Enter a valid phone number with country code (e.g. +919832581198).');
                }
            }
        };
    }
}
