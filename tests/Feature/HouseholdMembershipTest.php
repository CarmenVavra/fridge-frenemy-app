<?php

namespace Tests\Feature;

use App\Enums\ButlerTone;
use App\Models\FridgeItem;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseholdMembershipTest extends TestCase
{
    use RefreshDatabase;

    private Household $household;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->household = Household::factory()->create(['name' => 'Die Gregors']);
        $this->user = User::factory()->for($this->household)->create();
    }

    public function test_profile_shows_regenerate_and_join_forms(): void
    {
        $this->actingAs($this->user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee($this->household->invite_code)
            ->assertSee('Neuen Code erzeugen')
            ->assertSee('Anderem Haushalt beitreten')
            ->assertSee('name="confirm_leave"', false)
            ->assertSee('Du bist das einzige Mitglied');
    }

    public function test_profile_does_not_warn_about_deletion_when_others_remain(): void
    {
        User::factory()->for($this->household)->create();

        $this->actingAs($this->user)->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee('Du bist das einzige Mitglied')
            ->assertSee('Die anderen Mitglieder und alle Artikel bleiben dort.');
    }

    public function test_invite_code_can_be_regenerated(): void
    {
        $old = $this->household->invite_code;

        $this->actingAs($this->user)->from(route('profile.edit'))->post(route('profile.invite-code'))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('toast', 'Neuer Code. Der alte ist ab sofort wertlos.');

        $new = $this->household->fresh()?->invite_code;
        $this->assertNotSame($old, $new);
        $this->assertSame(8, strlen((string) $new));
    }

    public function test_old_invite_code_no_longer_works_for_registration(): void
    {
        $old = $this->household->invite_code;
        $this->household->regenerateInviteCode();

        $this->post(route('register'), [
            'name' => 'Lena',
            'email' => 'lena@example.com',
            'password' => 'geheim123',
            'password_confirmation' => 'geheim123',
            'invite_code' => $old,
        ])->assertSessionHasErrors('invite_code');

        $this->assertGuest();
    }

    public function test_guests_cannot_regenerate_the_invite_code(): void
    {
        $this->post(route('profile.invite-code'))->assertRedirect(route('login'));
    }

    public function test_user_can_join_another_household_and_keeps_the_old_one_for_others(): void
    {
        $roommate = User::factory()->for($this->household)->create();
        FridgeItem::factory()->for($this->household)->create(['name' => 'Gemeinsamer Käse']);
        $target = Household::factory()->create(['name' => 'WG Sonnenallee']);

        $this->actingAs($this->user)->post(route('profile.join'), [
            'invite_code' => ' '.strtolower($target->invite_code).' ',
            'confirm_leave' => '1',
        ])->assertRedirect(route('fridge.index'))
            ->assertSessionHas('toast', 'Willkommen bei WG Sonnenallee. Neuer Kühlschrank, neue Vorwürfe.');

        $this->assertSame($target->id, $this->user->fresh()?->household_id);
        $this->assertSame($this->household->id, $roommate->fresh()?->household_id);
        $this->assertDatabaseHas('fridge_items', ['name' => 'Gemeinsamer Käse', 'household_id' => $this->household->id]);
    }

    public function test_last_member_leaving_deletes_the_old_household(): void
    {
        FridgeItem::factory()->for($this->household)->create();
        $this->household->butlerAlerts()->create(['tone' => ButlerTone::Classic, 'message' => 'Der Käse wartet.']);
        $target = Household::factory()->create();

        $this->actingAs($this->user)->post(route('profile.join'), [
            'invite_code' => $target->invite_code,
            'confirm_leave' => '1',
        ])->assertRedirect(route('fridge.index'));

        $this->assertModelMissing($this->household);
        $this->assertDatabaseMissing('fridge_items', ['household_id' => $this->household->id]);
        $this->assertDatabaseMissing('butler_alerts', ['household_id' => $this->household->id]);
        $this->assertDatabaseMissing('household_stats', ['household_id' => $this->household->id]);
    }

    public function test_joining_requires_a_valid_foreign_code_and_confirmation(): void
    {
        $target = Household::factory()->create();

        $this->actingAs($this->user)->post(route('profile.join'), ['invite_code' => 'NIXDA123', 'confirm_leave' => '1'])
            ->assertSessionHasErrors(['invite_code' => 'Diesen Code kennt kein Kühlschrank.']);

        $this->actingAs($this->user)->post(route('profile.join'), ['invite_code' => $this->household->invite_code, 'confirm_leave' => '1'])
            ->assertSessionHasErrors(['invite_code' => 'Das ist dein eigener Haushalt. Da bist du schon.']);

        $this->actingAs($this->user)->post(route('profile.join'), ['invite_code' => $target->invite_code])
            ->assertSessionHasErrors('confirm_leave');

        $this->assertSame($this->household->id, $this->user->fresh()?->household_id);
        $this->assertModelExists($this->household);
    }

    public function test_guests_cannot_join_a_household(): void
    {
        $this->post(route('profile.join'), ['invite_code' => $this->household->invite_code, 'confirm_leave' => '1'])
            ->assertRedirect(route('login'));
    }
}
