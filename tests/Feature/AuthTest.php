<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/fach')->assertRedirect(route('login'));
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_login_page_renders(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Anmelden')
            ->assertSee('Passwort vergessen?');
    }

    public function test_registration_creates_a_new_household(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Carla',
            'email' => 'carla@example.test',
            'password' => 'geheim123',
            'password_confirmation' => 'geheim123',
            'household_name' => 'WG Testgasse',
        ]);

        $response->assertRedirect(route('fridge.index'));
        $this->assertAuthenticated();

        $user = User::where('email', 'carla@example.test')->firstOrFail();
        $this->assertSame('WG Testgasse', $user->household?->name);
        $this->assertDatabaseHas('household_stats', ['household_id' => $user->household_id, 'saved_count' => 0]);
    }

    public function test_registration_with_invite_code_joins_existing_household(): void
    {
        $household = Household::factory()->create(['invite_code' => 'JOINME12']);

        $this->post(route('register'), [
            'name' => 'Bea',
            'email' => 'bea@example.test',
            'password' => 'geheim123',
            'password_confirmation' => 'geheim123',
            'invite_code' => 'joinme12',
        ])->assertRedirect(route('fridge.index'));

        $this->assertSame($household->id, User::where('email', 'bea@example.test')->value('household_id'));
        $this->assertSame(1, Household::count());
    }

    public function test_registration_rejects_unknown_invite_code(): void
    {
        $this->post(route('register'), [
            'name' => 'Bea',
            'email' => 'bea@example.test',
            'password' => 'geheim123',
            'password_confirmation' => 'geheim123',
            'invite_code' => 'NOPE0000',
        ])->assertSessionHasErrors('invite_code');

        $this->assertGuest();
    }

    public function test_users_can_log_in_and_out(): void
    {
        $user = User::factory()->for(Household::factory())->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('fridge.index'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->for(Household::factory())->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'falsch'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_reset_link_can_be_requested_and_used(): void
    {
        Notification::fake();
        $user = User::factory()->for(Household::factory())->create();

        $this->get(route('password.request'))->assertOk()->assertSee('Link anfordern');
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $this->get(route('password.reset', ['token' => $notification->token, 'email' => $user->email]))
                ->assertOk()
                ->assertSee('Neues Passwort');

            $this->post(route('password.store'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'neuesPasswort1',
                'password_confirmation' => 'neuesPasswort1',
            ])->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(Hash::check('neuesPasswort1', (string) $user->fresh()?->password));
    }

    public function test_reset_link_request_does_not_reveal_unknown_emails(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'niemand@example.test'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_users_can_change_their_password(): void
    {
        $user = User::factory()->for(Household::factory())->create();

        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('Passwort ändern');

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'password',
            'password' => 'anderes123',
            'password_confirmation' => 'anderes123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('anderes123', (string) $user->fresh()?->password));
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->for(Household::factory())->create();

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'falsch',
            'password' => 'anderes123',
            'password_confirmation' => 'anderes123',
        ])->assertSessionHasErrors('current_password');
    }

    public function test_household_can_be_renamed(): void
    {
        $user = User::factory()->for(Household::factory())->create();

        $this->actingAs($user)->put(route('profile.household'), ['name' => 'WG Neu'])
            ->assertSessionHasNoErrors();

        $this->assertSame('WG Neu', $user->household()->value('name'));
    }
}
