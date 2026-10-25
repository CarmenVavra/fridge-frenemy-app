<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['legal.operator' => [
            'name' => 'Erika Muster',
            'street' => 'Musterweg 1',
            'city' => '1010 Wien',
            'country' => 'Österreich',
            'email' => 'hallo@example.com',
        ]]);
    }

    public function test_guests_can_read_the_imprint(): void
    {
        $this->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('Impressum')
            ->assertSee('Erika Muster')
            ->assertSee('Musterweg 1')
            ->assertSee('1010 Wien')
            ->assertSee('mailto:hallo@example.com', false)
            ->assertSee('§ 25 Mediengesetz');
    }

    public function test_guests_can_read_the_privacy_policy(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Datenschutzerklärung')
            ->assertSee('Erika Muster')
            ->assertSee('Open Food Facts')
            ->assertSee('netcup GmbH')
            ->assertSee(config('session.cookie'))
            ->assertSee('Datenschutzbehörde');
    }

    public function test_gemini_is_only_mentioned_when_it_is_used(): void
    {
        config(['services.gemini.key' => null]);
        $this->get(route('legal.privacy'))->assertDontSee('Google Gemini');

        config(['services.gemini.key' => 'test-key']);
        $this->get(route('legal.privacy'))->assertSee('Google Gemini');
    }

    public function test_missing_operator_details_are_flagged(): void
    {
        config(['legal.operator' => ['name' => null, 'street' => null, 'city' => null, 'country' => null, 'email' => null]]);

        $this->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('LEGAL_NAME')
            ->assertSee('LEGAL_CITY')
            ->assertSee('LEGAL_EMAIL');
    }

    public function test_logged_in_users_can_read_the_pages_and_get_back_to_the_app(): void
    {
        $user = User::factory()->for(Household::factory())->create();

        $this->actingAs($user)->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Zurück zur App');
    }

    public function test_legal_links_appear_on_login_register_and_profile(): void
    {
        $this->get(route('login'))->assertSee(route('legal.imprint'))->assertSee(route('legal.privacy'));
        $this->get(route('register'))->assertSee(route('legal.privacy'));

        $user = User::factory()->for(Household::factory())->create();
        $this->actingAs($user)->get(route('profile.edit'))
            ->assertSee(route('legal.imprint'))
            ->assertSee(route('legal.privacy'));
    }

    public function test_fonts_are_not_loaded_from_google(): void
    {
        $this->get(route('login'))->assertDontSee('fonts.googleapis.com');
    }
}
