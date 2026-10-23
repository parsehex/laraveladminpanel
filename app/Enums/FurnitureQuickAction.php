<?php

namespace App\Enums;

enum FurnitureQuickAction: string
{
    case Sell = 'sell';
    case ChangeStatus = 'status';
    case MarkAssembled = 'assemble';
    case SetLocation = 'location';

    public function label(): string
    {
        return match ($this) {
            self::Sell => 'Mark sold',
            self::ChangeStatus => 'Change status',
            self::MarkAssembled => 'Mark assembled',
            self::SetLocation => 'Set location',
        };
    }

    public function pastTense(): string
    {
        return match ($this) {
            self::Sell => 'Sold',
            self::ChangeStatus => 'Updated status on',
            self::MarkAssembled => 'Marked assembled',
            self::SetLocation => 'Set location on',
        };
    }
}
