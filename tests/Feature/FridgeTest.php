<?php

namespace Tests\Feature;

use App\Enums\FridgeItemStatus;
use App\Models\FridgeItem;
use App\Models\Household;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FridgeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->household = Household::factory()->create(['name' => 'WG Sonnenallee']);
        $this->user = User::factory()->for($this->household)->create();
    }

    public function test_dashboard_lists_active_items_with_butler_quotes(): void
    {
        FridgeItem::factory()->for($this->household)->expiresIn(-2)->create(['name' => 'Vollmilch']);
        FridgeItem::factory()->for($this->household)->expiresIn(9)->create(['name' => 'Eier']);
        FridgeItem::factory()->for($this->household)->create(['name' => 'Altlast', 'status' => FridgeItemStatus::Consumed]);

        $this->actingAs($this->user)->get(route('fridge.index'))
            ->assertOk()
            ->assertSee('Aggro-Barometer')
            ->assertSee('WG Sonnenallee')
            ->assertSee('Vollmilch')
            ->assertSee('Eier')
            ->assertSee('Vollmilch ist seit 2 Tagen abgelaufen', false)
            ->assertDontSee('Altlast');
    }

    public function test_dashboard_does_not_show_other_households_items(): void
    {
        FridgeItem::factory()->expiresIn(1)->create(['name' => 'Fremdkäse']);

        $this->actingAs($this->user)->get(route('fridge.index'))
            ->assertOk()
            ->assertDontSee('Fremdkäse');
    }

    public function test_item_can_be_stored(): void
    {
        $this->actingAs($this->user)->post(route('fridge.store'), [
            'name' => 'Hüttenkäse',
            'barcode' => '4008400402222',
            'category' => 'Milchprodukte',
            'quantity' => 2,
            'expires_at' => Carbon::today()->addDays(24)->toDateString(),
        ])->assertRedirect(route('fridge.index'))
            ->assertSessionHas('toast', 'Hüttenkäse eingelagert. Ich behalte es im Auge.');

        $item = $this->household->fridgeItems()->firstOrFail();
        $this->assertSame('Hüttenkäse', $item->name);
        $this->assertSame(2, $item->quantity);
        $this->assertSame('Stück', $item->unit);
        $this->assertTrue($item->expires_at->isSameDay(Carbon::today()->addDays(24)));
        $this->assertSame(FridgeItemStatus::Fresh, $item->status);
    }

    public function test_storing_an_item_validates_input(): void
    {
        $this->actingAs($this->user)->post(route('fridge.store'), [
            'name' => '',
            'quantity' => 0,
            'expires_at' => Carbon::yesterday()->toDateString(),
            'barcode' => 'abc',
        ])->assertSessionHasErrors(['name', 'quantity', 'expires_at', 'barcode']);

        $this->assertSame(0, FridgeItem::count());
    }

    public function test_storing_an_item_requires_a_real_date(): void
    {
        $this->actingAs($this->user)->post(route('fridge.store'), [
            'name' => 'Sauerrahm',
            'quantity' => 1,
            'expires_at' => '03.11.2026',
        ])->assertSessionHasErrors('expires_at');

        $this->assertSame(0, FridgeItem::count());
    }

    public function test_expiry_date_of_an_item_can_be_changed(): void
    {
        $item = FridgeItem::factory()->for($this->household)->expiresIn(1)->create(['name' => 'Mascarpone']);
        $this->assertSame(FridgeItemStatus::Warning, $item->status);

        $this->actingAs($this->user)->patch(route('fridge.expiry', $item), [
            'expires_at' => Carbon::today()->addDays(24)->toDateString(),
        ])->assertRedirect(route('fridge.index'))
            ->assertSessionHas('toast', 'Neues Datum für Mascarpone notiert. Ich vergesse nichts.');

        $item->refresh();
        $this->assertTrue($item->expires_at->isSameDay(Carbon::today()->addDays(24)));
        $this->assertSame(FridgeItemStatus::Fresh, $item->status);
    }

    public function test_dashboard_offers_the_date_form(): void
    {
        $item = FridgeItem::factory()->for($this->household)->expiresIn(10)->create();

        $this->actingAs($this->user)->get(route('fridge.index'))
            ->assertOk()
            // The card data is embedded via Js::from(), so the URL appears with escaped slashes.
            ->assertSee(str_replace('/', '\\\\\/', route('fridge.expiry', $item)), false)
            ->assertSee($item->expires_at->toDateString())
            ->assertSee('name="expires_at"', false);
    }

    public function test_changing_the_expiry_date_validates_input(): void
    {
        $item = FridgeItem::factory()->for($this->household)->expiresIn(10)->create();

        $this->actingAs($this->user)->from(route('fridge.index'))->patch(route('fridge.expiry', $item), [
            'expires_at' => Carbon::yesterday()->toDateString(),
        ])->assertRedirect(route('fridge.index'))
            ->assertSessionHasErrors(['expires_at' => 'Das Datum liegt in der Vergangenheit. Dann ist es schon abgelaufen.']);

        $this->assertTrue($item->fresh()?->expires_at->isSameDay(Carbon::today()->addDays(10)));
    }

    public function test_expiry_date_of_other_households_items_cannot_be_changed(): void
    {
        $foreign = FridgeItem::factory()->expiresIn(10)->create();

        $this->actingAs($this->user)->patch(route('fridge.expiry', $foreign), [
            'expires_at' => Carbon::today()->addDays(30)->toDateString(),
        ])->assertForbidden();

        $this->assertTrue($foreign->fresh()?->expires_at->isSameDay(Carbon::today()->addDays(10)));
    }

    public function test_expiry_date_of_a_resolved_item_cannot_be_changed(): void
    {
        $item = FridgeItem::factory()->for($this->household)->create(['status' => FridgeItemStatus::Consumed]);

        $this->actingAs($this->user)->patch(route('fridge.expiry', $item), [
            'expires_at' => Carbon::today()->addDays(30)->toDateString(),
        ])->assertStatus(409);
    }

    public function test_consuming_an_item_increments_the_hero_counter(): void
    {
        $item = FridgeItem::factory()->for($this->household)->expiresIn(0)->create();

        $this->actingAs($this->user)->patchJson(route('fridge.consume', $item))
            ->assertOk()
            ->assertJson(['saved' => 1, 'trashed' => 0, 'toast' => 'Gerettet. Der Kühlschrank ist fast stolz.']);

        $this->assertSame(FridgeItemStatus::Consumed, $item->fresh()?->status);
    }

    public function test_trashing_an_item_increments_the_guilt_counter(): void
    {
        $item = FridgeItem::factory()->for($this->household)->expiresIn(-1)->create();

        $this->actingAs($this->user)->patchJson(route('fridge.trash', $item))
            ->assertOk()
            ->assertJson(['saved' => 0, 'trashed' => 1]);

        $this->assertSame(FridgeItemStatus::Trashed, $item->fresh()?->status);
    }

    public function test_dashboard_offers_finding_an_item_by_barcode(): void
    {
        $this->actingAs($this->user)->get(route('fridge.index'))
            ->assertOk()
            ->assertSee('Artikel per Barcode finden')
            ->assertSee('ff-find-reader');
    }

    public function test_scanning_a_barcode_finds_the_matching_items_soonest_first(): void
    {
        $later = FridgeItem::factory()->for($this->household)->expiresIn(9)->create(['barcode' => '4008400402222']);
        $sooner = FridgeItem::factory()->for($this->household)->expiresIn(2)->create(['barcode' => '4008400402222']);
        FridgeItem::factory()->for($this->household)->expiresIn(1)->create(['barcode' => '4000000000001']);
        FridgeItem::factory()->for($this->household)->expiresIn(1)->create(['barcode' => '4008400402222', 'status' => FridgeItemStatus::Consumed]);
        FridgeItem::factory()->for(Household::factory())->expiresIn(1)->create(['barcode' => '4008400402222']);

        $this->actingAs($this->user)->postJson(route('fridge.find'), ['barcode' => '4008400402222'])
            ->assertOk()
            ->assertExactJson(['ids' => [$sooner->id, $later->id], 'message' => null]);
    }

    public function test_finding_reads_the_product_number_from_a_gs1_code(): void
    {
        $item = FridgeItem::factory()->for($this->household)->expiresIn(5)->create(['barcode' => '4008400402222']);

        $this->actingAs($this->user)->postJson(route('fridge.find'), ['barcode' => '0104008400402222172612311012345'])
            ->assertOk()
            ->assertJsonPath('ids', [$item->id]);
    }

    public function test_finding_an_item_that_is_not_in_the_fridge_says_so(): void
    {
        $this->actingAs($this->user)->postJson(route('fridge.find'), ['barcode' => '4008400402222'])
            ->assertOk()
            ->assertJson(['ids' => [], 'message' => 'Das liegt nicht im Kühlschrank. Zumindest nicht offiziell.']);
    }

    public function test_finding_rejects_codes_that_are_no_product_numbers(): void
    {
        $this->actingAs($this->user)->postJson(route('fridge.find'), ['barcode' => 'https://example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('barcode');
    }

    public function test_consuming_one_of_several_pieces_keeps_the_rest(): void
    {
        $item = FridgeItem::factory()->for($this->household)->expiresIn(10)->create(['name' => 'Kaffeejoghurt', 'quantity' => 2, 'category' => null]);

        $this->actingAs($this->user)->patchJson(route('fridge.consume', $item))
            ->assertOk()
            ->assertJson([
                'saved' => 1,
                'trashed' => 0,
                'remaining' => 1,
                'meta' => '1 Stück',
                'toast' => 'Eins gerettet, 1 übrig. Der Kühlschrank zählt mit.',
            ]);

        $item->refresh();
        $this->assertSame(1, $item->quantity);
        $this->assertSame(FridgeItemStatus::Fresh, $item->status);
    }

    public function test_trashing_pieces_one_by_one_resolves_the_last_one(): void
    {
        $item = FridgeItem::factory()->for($this->household)->expiresIn(-1)->create(['quantity' => 2]);

        $this->actingAs($this->user)->patchJson(route('fridge.trash', $item))
            ->assertJson(['trashed' => 1, 'remaining' => 1, 'toast' => 'Eins weg, 1 übrig. Der Schuld-Counter dankt dir.']);
        $this->actingAs($this->user)->patchJson(route('fridge.trash', $item))
            ->assertJson(['trashed' => 2, 'remaining' => 0, 'toast' => 'Verbucht. Der Schuld-Counter dankt dir.']);

        $this->assertSame(FridgeItemStatus::Trashed, $item->fresh()?->status);
        $this->assertSame(1, $item->fresh()?->quantity);
    }

    public function test_resolving_without_javascript_redirects_back(): void
    {
        $item = FridgeItem::factory()->for($this->household)->create();

        $this->actingAs($this->user)->patch(route('fridge.consume', $item))
            ->assertRedirect(route('fridge.index'));
    }

    public function test_an_item_cannot_be_resolved_twice(): void
    {
        $item = FridgeItem::factory()->for($this->household)->create(['status' => FridgeItemStatus::Trashed]);

        $this->actingAs($this->user)->patchJson(route('fridge.consume', $item))->assertStatus(409);
        $this->assertSame(0, $this->household->statsOrCreate()->saved_count);
    }

    public function test_items_of_other_households_cannot_be_changed(): void
    {
        // Fester Abstand: bei 1-2 Tagen setzt das Model den Status sonst auf "warning".
        $foreign = FridgeItem::factory()->expiresIn(10)->create();

        $this->actingAs($this->user)->patchJson(route('fridge.trash', $foreign))->assertForbidden();
        $this->assertSame(FridgeItemStatus::Fresh, $foreign->fresh()?->status);
    }

    public function test_requests_without_csrf_token_are_rejected_outside_tests(): void
    {
        // Laravel skips CSRF verification while running unit tests; make sure the middleware is registered.
        $this->assertContains(
            ValidateCsrfToken::class,
            app(Kernel::class)->getMiddlewareGroups()['web']
        );
    }
}
