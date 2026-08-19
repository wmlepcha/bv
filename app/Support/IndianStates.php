<?php

namespace App\Support;

class IndianStates
{
    /**
     * @return list<array{name: string, code: string}>
     */
    public static function all(): array
    {
        /** @var list<array{name: string, code: string}> $states */
        $states = config('indian_states', []);

        return $states;
    }

    /**
     * @return array<string, string> name => code
     */
    public static function nameToCodeMap(): array
    {
        $map = [];

        foreach (self::all() as $state) {
            $map[$state['name']] = $state['code'];
        }

        return $map;
    }

    /**
     * @return array<string, string> code => name
     */
    public static function codeToNameMap(): array
    {
        $map = [];

        foreach (self::all() as $state) {
            $map[$state['code']] = $state['name'];
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_column(self::all(), 'name');
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_column(self::all(), 'code');
    }

    /**
     * @return array<string, string> name => name (for select options)
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::names() as $name) {
            $options[$name] = $name;
        }

        return $options;
    }

    public static function codeForName(?string $name): ?string
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        $normalized = mb_strtolower(trim($name));

        foreach (self::all() as $state) {
            if (mb_strtolower($state['name']) === $normalized) {
                return $state['code'];
            }
        }

        // Allow passing a code directly.
        $upper = strtoupper(trim($name));

        if (in_array($upper, self::codes(), true)) {
            return $upper;
        }

        return null;
    }

    public static function isValidName(?string $name): bool
    {
        return self::codeForName($name) !== null;
    }
}
