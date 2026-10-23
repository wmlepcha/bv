<?php

namespace App\Enums;

enum AssignmentTargetScope: string
{
    case Batch = 'batch';
    case Student = 'student';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
