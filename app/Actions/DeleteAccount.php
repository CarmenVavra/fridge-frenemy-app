<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a user account for good (GDPR right to erasure).
 *
 * If the user was the last member, the household goes too, together with its
 * items, stats and alerts (cascading foreign keys). Open password reset links
 * and stored sessions of the user are removed as well.
 */
class DeleteAccount
{
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $household = $user->household;

            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();

            $user->delete();

            if ($household !== null && ! $household->users()->exists()) {
                $household->delete();
            }
        });
    }
}
