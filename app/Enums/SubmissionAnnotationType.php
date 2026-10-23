<?php

namespace App\Enums;

enum SubmissionAnnotationType: string
{
    case Highlight = 'highlight';
    case Memo = 'memo';
    case Pen = 'pen';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
