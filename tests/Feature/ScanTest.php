<?php

namespace Tests\Feature;

use App\Models\FridgeItem;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->for(Household::factory())->create();
    }

    public function test_scan_page_renders(): void
    {
        $this->actingAs($this->user)->get(route('scan.index'))
            ->assertOk()
            ->assertSee('Neuer Verdächtiger')
            ->assertSee('Barcode scannen')
            ->assertSee('Manuell eintragen')
            ->assertSee('name="expires_at" type="date"', false)
            ->assertSee('Das Datum steht nicht im Barcode.');
    }

    public function test_barcode_lookup_returns_open_food_facts_product(): void
    {
        Http::fake([
            'world.openfoodfacts.org/*' => Http::response([
                'status' => 1,
                'product' => [
                    'product_name' => 'Cottage Cheese',
                    'product_name_de' => 'Hüttenkäse Natur',
                    'categories' => 'Milchprodukte, Käse, en:cottage-cheeses',
                ],
            ]),
        ]);

        $this->actingAs($this->user)->postJson(route('scan.lookup'), ['barcode' => '4008400402222'])
            ->assertOk()
            ->assertJson([
                'found' => true,
                'barcode' => '4008400402222',
                'name' => 'Hüttenkäse Natur',
                'category' => 'cottage-cheeses',
            ]);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/product/4008400402222.json')
            && $request->hasHeader('User-Agent'));
    }

    public function test_unknown_barcode_returns_404(): void
    {
        Http::fake(['world.openfoodfacts.org/*' => Http::response(['status' => 0], 404)]);

        $this->actingAs($this->user)->postJson(route('scan.lookup'), ['barcode' => '0000000000000'])
            ->assertNotFound()
            ->assertJson(['found' => false]);
    }

    public function test_barcode_must_be_numeric(): void
    {
        Http::fake();

        $this->actingAs($this->user)->postJson(route('scan.lookup'), ['barcode' => '../../etc'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('barcode');

        Http::assertNothingSent();
    }

    public function test_guests_cannot_use_the_lookup(): void
    {
        $this->postJson(route('scan.lookup'), ['barcode' => '4008400402222'])->assertUnauthorized();
    }

    public function test_scan_page_offers_the_date_scanner(): void
    {
        $this->actingAs($this->user)->get(route('scan.index'))
            ->assertOk()
            ->assertSee('Datum von der Packung scannen')
            ->assertSee('x-ref="dateVideo"', false)
            // The config is embedded via Js::from(), so the URL appears with escaped slashes.
            ->assertSee(str_replace('/', '\\\\\/', route('scan.date')), false);
    }

    public function test_known_barcode_is_answered_from_household_memory(): void
    {
        Http::fake();
        $this->travelTo(Carbon::parse('2026-10-20 18:30'));
        FridgeItem::factory()->for($this->user->household)->create([
            'barcode' => '9001234567890',
            'name' => 'Sauerrahm 15% Fett',
            'category' => 'Milchprodukte',
            'expires_at' => '2026-10-29',
        ]);
        $this->travelBack();

        $this->actingAs($this->user)->postJson(route('scan.lookup'), ['barcode' => '9001234567890'])
            ->assertOk()
            ->assertJson([
                'found' => true,
                'source' => 'memory',
                'name' => 'Sauerrahm 15% Fett',
                'category' => 'Milchprodukte',
                'remembered_days' => 9,
                'expires_at' => null,
            ]);

        Http::assertNothingSent();
    }

    public function test_memory_of_other_households_is_not_used(): void
    {
        Http::fake(['world.openfoodfacts.org/*' => Http::response(['status' => 0], 404)]);
        FridgeItem::factory()->create(['barcode' => '9001234567890', 'name' => 'Fremder Rahm']);

        $this->actingAs($this->user)->postJson(route('scan.lookup'), ['barcode' => '9001234567890'])
            ->assertNotFound()
            ->assertJsonMissing(['name' => 'Fremder Rahm']);
    }

    public function test_gs1_datamatrix_code_provides_product_and_expiry_date(): void
    {
        Http::fake([
            'world.openfoodfacts.org/*' => Http::response(['status' => 1, 'product' => ['product_name' => 'Mascarpone']]),
        ]);
        $code = "\x1D".'0104008400402222'.'17'.Carbon::today()->addDays(30)->format('ymd').'10LOT42';

        $this->actingAs($this->user)->postJson(route('scan.lookup'), ['barcode' => $code])
            ->assertOk()
            ->assertJson([
                'found' => true,
                'barcode' => '4008400402222',
                'name' => 'Mascarpone',
                'expires_at' => Carbon::today()->addDays(30)->toDateString(),
            ]);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/product/4008400402222.json'));
    }

    public function test_gs1_digital_link_qr_code_provides_expiry_even_for_unknown_products(): void
    {
        Http::fake(['world.openfoodfacts.org/*' => Http::response(['status' => 0], 404)]);

        $this->actingAs($this->user)->postJson(route('scan.lookup'), ['barcode' => 'https://id.gs1.org/01/09506000134352/10/ABC?17=261100'])
            ->assertNotFound()
            ->assertJson([
                'found' => false,
                'barcode' => '9506000134352',
                'expires_at' => '2026-11-30',
            ]);
    }

    public function test_non_product_qr_codes_are_rejected(): void
    {
        Http::fake();

        $this->actingAs($this->user)->postJson(route('scan.lookup'), ['barcode' => 'https://example.com/gewinnspiel'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['barcode' => 'Das ist kein Produkt-Code.']);

        Http::assertNothingSent();
    }

    public function test_expiry_date_is_detected_in_ocr_text(): void
    {
        $this->travelTo(Carbon::parse('2026-10-25'));

        $this->actingAs($this->user)->postJson(route('scan.date'), ['text' => "L 2410\n12.10.26  03.11.26"])
            ->assertOk()
            ->assertExactJson(['found' => true, 'expires_at' => '2026-11-03']);
    }

    public function test_no_date_in_ocr_text_is_reported(): void
    {
        $this->actingAs($this->user)->postJson(route('scan.date'), ['text' => '4008400 402222'])
            ->assertOk()
            ->assertExactJson(['found' => false, 'expires_at' => null]);
    }

    public function test_date_detection_validates_input(): void
    {
        $this->actingAs($this->user)->postJson(route('scan.date'), ['text' => str_repeat('1', 1001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('text');
    }

    public function test_guests_cannot_use_date_detection(): void
    {
        $this->postJson(route('scan.date'), ['text' => '03.11.26'])->assertUnauthorized();
    }
}
