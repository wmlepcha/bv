<?php

namespace App\Enums;

enum AvatarAnimal: string
{
    case Fox = 'fox';
    case Panda = 'panda';
    case Cat = 'cat';
    case Dog = 'dog';
    case Owl = 'owl';
    case Bear = 'bear';
    case Rabbit = 'rabbit';
    case Frog = 'frog';
    case Penguin = 'penguin';
    case Koala = 'koala';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * A stable pseudo-random animal for a user who has not picked one.
     */
    public static function defaultFor(int $userId): self
    {
        $cases = self::cases();

        return $cases[crc32((string) $userId) % count($cases)];
    }
}
