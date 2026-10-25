<?php

namespace Tests\Feature;

use App\Models\FridgeItem;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_offers_account_deletion(): void
    {
        $user = User::factory()->for(Household::factory())->create();

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Konto löschen')
            ->assertSee(route('profile.destroy'));
    }

    public function test_last_member_deletes_account_and_household(): void
    {
        $household = Household::factory()->create();
        $user = User::factory()->for($household)->create();
        $item = FridgeItem::factory()->for($household)->create();
        DB::table('password_reset_tokens')->insert(['email' => $user->email, 'token' => 'x', 'created_at' => now()]);

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertGuest();
        $this->assertModelMissing($user);
        $this->assertModelMissing($household);
        $this->assertModelMissing($item);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_household_stays_for_remaining_members(): void
    {
        $household = Household::factory()->create();
        $user = User::factory()->for($household)->create();
        $flatmate = User::factory()->for($household)->create();
        $item = FridgeItem::factory()->for($household)->create();

        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password']);

        $this->assertModelMissing($user);
        $this->assertModelExists($flatmate);
        $this->assertModelExists($household);
        $this->assertModelExists($item);
    }

    public function test_wrong_password_keeps_the_account(): void
    {
        $user = User::factory()->for(Household::factory())->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), ['password' => 'falsch'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrorsIn('deleteAccount', 'password');

        $this->assertModelExists($user);
        $this->assertAuthenticatedAs($user);
    }

    public function test_guests_cannot_delete_accounts(): void
    {
        $this->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect(route('login'));
    }

    public function test_privacy_policy_points_to_self_service_deletion(): void
    {
        $this->get(route('legal.privacy'))->assertSee('Konto löschen');
    }
}
