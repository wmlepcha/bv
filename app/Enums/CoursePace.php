<?php

namespace App\Enums;

enum CoursePace: string
{
    case TooFast = 'too_fast';
    case Appropriate = 'appropriate';
    case TooSlow = 'too_slow';

    public function label(): string
    {
        return match ($this) {
            self::TooFast => 'Too Fast',
            self::Appropriate => 'Appropriate',
            self::TooSlow => 'Too Slow',
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

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
