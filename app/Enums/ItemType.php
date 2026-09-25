<?php

namespace App\Enums;

enum ItemType: string
{
    case Appliance = 'appliance';
    case Furniture = 'furniture';

    public function label(): string
    {
        return match ($this) {
            self::Appliance => 'Appliance',
            self::Furniture => 'Furniture',
        };
    }
}
