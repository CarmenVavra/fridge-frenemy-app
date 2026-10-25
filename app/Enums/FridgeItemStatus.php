<?php

namespace App\Enums;

enum FridgeItemStatus: string
{
    case Fresh = 'fresh';
    case Warning = 'warning';
    case Expired = 'expired';
    case Consumed = 'consumed';
    case Trashed = 'trashed';

    /**
     * Statuses of items that are still physically in the fridge.
     *
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Fresh, self::Warning, self::Expired];
    }

    public function isResolved(): bool
    {
        return in_array($this, [self::Consumed, self::Trashed], true);
    }
}
