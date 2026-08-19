<?php

namespace App\Enums;

enum CourseLanguage: string
{
    case German = 'german';
    case Japanese = 'japanese';
    case French = 'french';
    case Spanish = 'spanish';

    public function label(): string
    {
        return match ($this) {
            self::German => 'German',
            self::Japanese => 'Japanese',
            self::French => 'French',
            self::Spanish => 'Spanish',
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
