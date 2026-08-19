<?php

namespace App\Enums;

enum CourseLevel: string
{
    case A1 = 'A1';
    case A2 = 'A2';
    case B1 = 'B1';
    case B2 = 'B2';

    public function label(): string
    {
        return $this->value;
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
