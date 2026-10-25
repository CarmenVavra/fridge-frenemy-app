<?php

namespace Tests\Feature;

use App\Enums\ButlerTone;
use App\Models\ButlerAlert;
use App\Models\FridgeItem;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ButlerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->household = Household::factory()->create();
        $this->user = User::factory()->for($this->household)->create();
    }

    public function test_butler_page_lists_all_tones(): void
    {
        $response = $this->actingAs($this->user)->get(route('butler.index'))->assertOk();

        foreach (ButlerTone::cases() as $tone) {
            $response->assertSee($tone->label());
        }
        $response->assertSee('Alarm testen');
    }

    public function test_tone_can_be_changed(): void
    {
        $this->actingAs($this->user)->put(route('butler.tone'), ['tone' => 'shakespeare'])
            ->assertRedirect(route('butler.index'));

        $this->assertSame(ButlerTone::Shakespeare, $this->household->fresh()?->passive_aggressive_tone);
    }

    public function test_invalid_tone_is_rejected(): void
    {
        $this->actingAs($this->user)->put(route('butler.tone'), ['tone' => 'gangsta'])
            ->assertSessionHasErrors('tone');
    }

    public function test_changing_the_tone_changes_the_quotes(): void
    {
        FridgeItem::factory()->for($this->household)->expiresIn(0)->create(['name' => 'Joghurt']);
        $this->household->update(['passive_aggressive_tone' => ButlerTone::ItalianMom]);

        $this->actingAs($this->user)->get(route('fridge.index'))
            ->assertSee('Mangia, mangia!');
    }

    public function test_test_alert_uses_fallback_without_gemini_key(): void
    {
        Http::fake();
        FridgeItem::factory()->for($this->household)->expiresIn(-2)->create(['name' => 'Vollmilch']);

        $this->actingAs($this->user)->postJson(route('butler.test'))
            ->assertOk()
            ->assertJson(['message' => 'Vollmilch ist seit 2 Tagen abgelaufen. Ich hoffe, du hast eine gute Krankenversicherung.']);

        Http::assertNothingSent();
        $this->assertDatabaseHas('butler_alerts', ['household_id' => $this->household->id, 'source' => 'fallback']);
    }

    public function test_test_alert_uses_gemini_when_configured(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => '„Der Käse hat Gefühle.“']]]]],
            ]),
        ]);
        $item = FridgeItem::factory()->for($this->household)->expiresIn(1)->create(['name' => 'Gouda']);

        $this->actingAs($this->user)->postJson(route('butler.test'))
            ->assertOk()
            ->assertJson(['message' => 'Der Käse hat Gefühle.']);

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('x-goog-api-key', 'test-key')
            && str_contains((string) $request['contents'][0]['parts'][0]['text'], 'Gouda'));
        $this->assertSame(1, $item->fresh()?->annoyance_level);
    }

    public function test_test_alert_falls_back_when_gemini_fails(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 500)]);
        FridgeItem::factory()->for($this->household)->expiresIn(0)->create(['name' => 'Joghurt']);

        $this->actingAs($this->user)->postJson(route('butler.test'))
            ->assertOk()
            ->assertJson(['message' => 'Joghurt läuft heute ab. Heute. Nicht irgendwann. Heute.']);
    }

    public function test_test_alert_with_empty_fridge_gives_all_clear(): void
    {
        $this->actingAs($this->user)->postJson(route('butler.test'))
            ->assertOk()
            ->assertJson(['message' => ButlerTone::Classic->allClear()]);
    }

    public function test_pending_alert_is_shown_and_can_be_dismissed(): void
    {
        $alert = $this->household->butlerAlerts()->create([
            'tone' => ButlerTone::Classic,
            'message' => 'Guten Morgen, Verschwender.',
        ]);

        $this->actingAs($this->user)->get(route('fridge.index'))->assertSee('Guten Morgen, Verschwender.');

        $this->actingAs($this->user)->patchJson(route('butler.alerts.dismiss', $alert))->assertNoContent();
        $this->assertNotNull($alert->fresh()?->dismissed_at);

        $this->actingAs($this->user)->get(route('fridge.index'))->assertDontSee('Guten Morgen, Verschwender.');
    }

    public function test_alerts_of_other_households_cannot_be_dismissed(): void
    {
        $alert = ButlerAlert::query()->forceCreate([
            'household_id' => Household::factory()->create()->id,
            'tone' => 'classic',
            'message' => 'Fremd',
        ]);

        $this->actingAs($this->user)->patchJson(route('butler.alerts.dismiss', $alert))->assertForbidden();
    }
}
