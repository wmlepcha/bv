<?php

namespace App\Enums;

enum PlacementStatus: string
{
    case Open = 'open';
    case Hired = 'hired';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open for work',
            self::Hired => 'Placed',
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
