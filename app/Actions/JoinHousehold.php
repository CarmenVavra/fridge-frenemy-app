<?php

namespace App\Actions;

use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Moves an existing user into another household via its invite code.
 *
 * A household left without members is deleted together with its items,
 * stats and alerts (cascading foreign keys), so no orphaned fridge remains.
 */
class JoinHousehold
{
    public function handle(User $user, string $inviteCode): Household
    {
        return DB::transaction(function () use ($user, $inviteCode): Household {
            $target = Household::query()->where('invite_code', $inviteCode)->firstOrFail();
            $previous = $user->household;

            $user->household()->associate($target);
            $user->save();

            if ($previous !== null && ! $previous->users()->exists()) {
                $previous->delete();
            }

            return $target;
        });
    }
}
