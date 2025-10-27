<?php

namespace App\Enums\Restaurant;

enum RestaurantStatus: string
{
    case active = 'active';
    case deactive = 'deactive';

     public function label(): string
    {
        return match ($this) {
            self::active => 'Active',
            self::deactive => 'Deactive',
        };
    }
}
