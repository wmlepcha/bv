<?php

namespace App\Enums;

enum Gender: string
{
    case Female = 'Female';
    case Male = 'Male';
    case Other = 'Other';
    case PreferNotToSay = 'Prefer not to say';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
