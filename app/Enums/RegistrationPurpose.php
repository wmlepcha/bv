<?php

namespace App\Enums;

enum RegistrationPurpose: string
{
    case Training = 'training';
    case TrainingAndPlacement = 'training_and_placement';

    public function label(): string
    {
        return match ($this) {
            self::Training => 'Training only',
            self::TrainingAndPlacement => 'Training + Placement',
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
}
