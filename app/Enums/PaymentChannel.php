<?php

namespace App\Enums;

enum PaymentChannel: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Legacy = 'legacy';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Offline => 'Offline',
            self::Legacy => 'Earlier (mapped)',
        };
    }
}
