<?php

namespace App\Enums;

enum Profession: string
{
    case NurseRegistration = 'nurse_registration';
    case Physiotherapist = 'physiotherapist';
    case ClassXiiPass = 'class_xii_pass';

    public function label(): string
    {
        return match ($this) {
            self::NurseRegistration => 'Nurse registration',
            self::Physiotherapist => 'Physiotherapist',
            self::ClassXiiPass => 'Class XII pass',
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
