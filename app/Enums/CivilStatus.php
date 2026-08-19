<?php

namespace App\Enums;

enum CivilStatus: string
{
    case Unmarried = 'Unmarried';
    case Married = 'Married';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
