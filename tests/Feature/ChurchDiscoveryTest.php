<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\ChurchLocation;
use Database\Seeders\ChurchSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ChurchDiscoveryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_search_only_displays_locations_matching_all_filters(): void
    {
        $church = Church::factory()->create(['name' => 'Comunidade encontrada']);
        ChurchLocation::factory()->for($church)->create(['city' => 'Curitiba', 'neighborhood' => 'Batel']);
        ChurchLocation::factory()->for($church)->create(['city' => 'Londrina', 'neighborhood' => 'Unidade fora do destino']);
        ChurchLocation::factory()->create(['city' => 'Curitiba', 'state' => 'SC']);
        ChurchLocation::factory()->create(['welcomes_visitors' => false]);
        ChurchLocation::factory()->for(Church::factory()->create(['denomination' => 'Presbiteriana']))->create();

        $this->get('/igrejas?city=curitiba&state=pr&denomination=Batista&visitors=1')
            ->assertOk()->assertSee('Comunidade encontrada')->assertSee('Batel')
            ->assertDontSee('Unidade fora do destino')
            ->assertViewHas('churches', fn ($churches): bool => $churches->total() === 1);
    }

    public function test_non_public_churches_are_hidden_from_search_and_direct_links(): void
    {
        $suspended = Church::factory()->suspended()->hasLocations()->create(['name' => 'Igreja suspensa']);
        $draft = Church::factory()->hasLocations()->create(['status' => 'draft', 'name' => 'Igreja rascunho']);

        $this->get('/igrejas')->assertOk()->assertDontSee('Igreja suspensa')->assertDontSee('Igreja rascunho');
        $this->get(route('churches.show', $suspended))->assertNotFound();
        $this->get(route('churches.show', $draft))->assertNotFound();
    }

    public function test_details_show_multiple_units_and_escape_institutional_content(): void
    {
        $church = Church::factory()->hasLocations(2)->create(['description' => '<script>alert(1)</script>']);

        $this->get(route('churches.show', $church))->assertOk()
            ->assertSee('Horários habituais')->assertSee('Não representa um evento confirmado')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertViewHas('church', fn (Church $church): bool => $church->locations->count() === 2);
    }

    public function test_empty_search_offers_a_useful_message(): void
    {
        $this->get('/igrejas?city=Recife')->assertOk()->assertSee('Ainda não encontramos uma igreja por aqui.');
    }

    public function test_invalid_city_returns_validation_feedback(): void
    {
        $this->from('/igrejas')->get('/igrejas?city='.str_repeat('a', 101))
            ->assertRedirect('/igrejas')->assertSessionHasErrors(['city' => 'Informe uma cidade com até 100 caracteres.']);
    }

    public function test_pagination_preserves_search_filters(): void
    {
        Church::factory()->hasLocations()->count(10)->create();

        $this->get('/igrejas?city=Curitiba&visitors=1')->assertOk()
            ->assertSee('city=Curitiba&amp;visitors=1&amp;page=2', false);
    }

    public function test_demo_seeder_can_be_repeated_without_duplicates(): void
    {
        $this->seed(ChurchSeeder::class);
        $this->seed(ChurchSeeder::class);

        $this->assertDatabaseCount('churches', 3);
        $this->assertDatabaseCount('church_locations', 6);
    }
}
