<?php

namespace App\Enums;

enum AssignmentSubmissionStatus: string
{
    case Submitted = 'submitted';
    case Graded = 'graded';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
