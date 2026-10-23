<?php

namespace App\Enums;

enum FeedbackSkill: string
{
    case Speaking = 'speaking';
    case Listening = 'listening';
    case Reading = 'reading';
    case Writing = 'writing';
    case Grammar = 'grammar';
    case Vocabulary = 'vocabulary';

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

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
