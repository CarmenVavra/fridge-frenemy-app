<?php

namespace App\Actions;

use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates a user and either a new household or membership in an existing one.
 */
class RegisterUser
{
    /**
     * @param  array{name: string, email: string, password: string, household_name?: string|null, invite_code?: string|null}  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $inviteCode = strtoupper(trim((string) ($data['invite_code'] ?? '')));

            $household = $inviteCode !== ''
                ? Household::query()->where('invite_code', $inviteCode)->firstOrFail()
                : Household::create(['name' => $data['household_name'] ?: $data['name'].'s Kühlschrank']);

            $user = new User([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            $user->household()->associate($household);
            $user->save();

            return $user;
        });
    }
}
