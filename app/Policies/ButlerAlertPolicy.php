<?php

namespace App\Policies;

use App\Models\ButlerAlert;
use App\Models\User;

class ButlerAlertPolicy
{
    /**
     * Only members of the owning household may dismiss an alert.
     */
    public function update(User $user, ButlerAlert $alert): bool
    {
        return $user->household_id !== null && $user->household_id === $alert->household_id;
    }
}
