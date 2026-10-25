<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
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
            ->assertSee('Manuell eintragen');
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
}
