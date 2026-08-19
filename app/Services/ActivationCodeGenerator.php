<?php

namespace App\Services;

use App\Models\ActivationCode;
use Illuminate\Support\Str;

class ActivationCodeGenerator
{
    public const PREFIX = 'BV-26-';

    private const SUFFIX_LENGTH = 4;

    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function generate(): string
    {
        $attempts = 0;

        do {
            $code = self::PREFIX.$this->randomSuffix();
            $attempts++;

            if ($attempts > 100) {
                throw new \RuntimeException('Unable to generate a unique activation code.');
            }
        } while (ActivationCode::query()->where('code', $code)->exists());

        return $code;
    }

    private function randomSuffix(): string
    {
        $alphabetLength = strlen(self::ALPHABET);
        $suffix = '';

        for ($i = 0; $i < self::SUFFIX_LENGTH; $i++) {
            $suffix .= self::ALPHABET[random_int(0, $alphabetLength - 1)];
        }

        return $suffix;
    }

    public function isValidFormat(string $code): bool
    {
        return (bool) preg_match(
            '/^BV-26-[A-Z2-9]{4}$/',
            Str::upper($code),
        );
    }
}
