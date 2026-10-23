<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Excused = 'excused';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::Excused => 'Leave',
        };
    }
}
