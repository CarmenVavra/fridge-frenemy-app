<?php

namespace App\Policies;

use App\Models\FridgeItem;
use App\Models\User;

class FridgeItemPolicy
{
    /**
     * Only members of the owning household may change an item.
     */
    public function update(User $user, FridgeItem $item): bool
    {
        return $user->household_id !== null && $user->household_id === $item->household_id;
    }
}
