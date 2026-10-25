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
            'shelf_days' => 7,
        ])->assertRedirect(route('fridge.index'))
            ->assertSessionHas('toast', 'Hüttenkäse eingelagert. Ich behalte es im Auge.');

        $item = $this->household->fridgeItems()->firstOrFail();
        $this->assertSame('Hüttenkäse', $item->name);
        $this->assertSame(2, $item->quantity);
        $this->assertSame('Stück', $item->unit);
        $this->assertTrue($item->expires_at->isSameDay(Carbon::today()->addDays(7)));
        $this->assertSame(FridgeItemStatus::Fresh, $item->status);
    }

    public function test_storing_an_item_validates_input(): void
    {
        $this->actingAs($this->user)->post(route('fridge.store'), [
            'name' => '',
            'quantity' => 0,
            'shelf_days' => -1,
            'barcode' => 'abc',
        ])->assertSessionHasErrors(['name', 'quantity', 'shelf_days', 'barcode']);

        $this->assertSame(0, FridgeItem::count());
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
        $foreign = FridgeItem::factory()->create();

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
