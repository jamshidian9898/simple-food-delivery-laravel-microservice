<?php

namespace App\Enums\Basket;

enum BasketStatus: string
{
    case active = 'active';
    case ordered = 'ordered';
    case expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::active => 'Active',
            self::ordered => 'Ordered',
            self::expired => 'Expired',
        };
    }
}
