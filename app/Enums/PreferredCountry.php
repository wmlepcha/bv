<?php

namespace App\Enums;

enum PreferredCountry: string
{
    case Germany = 'germany';
    case Austria = 'austria';
    case NotSure = 'not_sure';

    public function label(): string
    {
        return match ($this) {
            self::Germany => 'Germany',
            self::Austria => 'Austria',
            self::NotSure => 'Not Sure',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
