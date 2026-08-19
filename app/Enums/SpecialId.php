<?php

namespace App\Enums;

enum SpecialId: string
{
    case Coi = 'COI';
    case Rc = 'RC';
    case Vc = 'VC';
    case Oth = 'OTH';

    public function label(): string
    {
        return match ($this) {
            self::Coi => 'Certificate of Identity',
            self::Rc => 'Resident certificate',
            self::Vc => 'Voter card',
            self::Oth => 'Others',
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
