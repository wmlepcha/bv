<?php

namespace App\Enums;

enum ReferralInterest: string
{
    case Ausbildung = 'ausbildung';
    case Nursing = 'nursing';
    case Physiotherapy = 'physiotherapy';
    case Other = 'other';

    public function label(): string
    {
        return ucfirst($this->value);
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
}
