<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Community;
use App\Models\CommunityPost;
use Database\Seeders\CommunitySeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CommunityDiscoveryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_community_search_combines_name_city_and_type(): void
    {
        Community::factory()->create(['name' => 'Jovens do Caminho']);
        Community::factory()->create(['name' => 'Jovens de outra cidade', 'city' => 'Recife']);
        Community::factory()->create(['name' => 'Jovens em oração', 'type' => 'oracao']);
        Community::factory()->create(['name' => 'Outro grupo']);

        $this->get('/comunidades?q=jovens&city=curitiba&type=jovens')->assertOk()
            ->assertSee('Jovens do Caminho')->assertDontSee('Jovens de outra cidade')
            ->assertDontSee('Jovens em oração')->assertDontSee('Outro grupo')
            ->assertViewHas('communities', fn ($communities): bool => $communities->total() === 1);
    }

    public function test_private_draft_and_suspended_church_groups_are_hidden_everywhere(): void
    {
        $this->freezeTime();
        $private = Community::factory()->create(['visibility' => 'private']);
        $draft = Community::factory()->create(['status' => 'draft']);
        $suspended = Community::factory()->for(Church::factory()->suspended())->create();
        foreach ([$private, $draft, $suspended] as $community) {
            CommunityPost::factory()->for($community)->create(['title' => 'Conteúdo confidencial']);
            $this->get(route('communities.show', $community->slug))->assertNotFound();
        }

        $this->get('/')->assertOk()->assertDontSee('Conteúdo confidencial');
        $this->get('/comunidades')->assertOk()->assertViewHas('communities', fn ($communities): bool => $communities->total() === 0);
    }

    public function test_feed_and_community_only_show_public_published_posts_that_are_due(): void
    {
        $this->freezeTime();
        $community = Community::factory()->create(['visibility' => 'approval']);
        CommunityPost::factory()->for($community)->create(['title' => 'Aviso público']);
        CommunityPost::factory()->for($community)->create(['title' => 'Somente membros', 'audience' => 'members']);
        CommunityPost::factory()->for($community)->create(['title' => 'Rascunho confidencial', 'status' => 'draft']);
        CommunityPost::factory()->for($community)->create(['title' => 'Publicação futura', 'published_at' => now()->addDay()]);

        foreach (['/', route('communities.show', $community->slug)] as $url) {
            $this->get($url)->assertOk()->assertSee('Aviso público')->assertDontSee('Somente membros')
                ->assertDontSee('Rascunho confidencial')->assertDontSee('Publicação futura');
        }
    }

    public function test_community_page_escapes_content_and_links_to_its_church(): void
    {
        $church = Church::factory()->create();
        $community = Community::factory()->for($church)->create(['description' => '<script>alert(1)</script>']);

        $this->get(route('communities.show', $community->slug))->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee(route('churches.show', $church), false);
    }

    public function test_unknown_category_is_rejected(): void
    {
        $this->from('/comunidades')->get('/comunidades?type=invalid')->assertRedirect('/comunidades')->assertSessionHasErrors('type');
    }

    public function test_empty_search_provides_a_way_to_clear_filters(): void
    {
        $this->get('/comunidades?city=Recife')->assertOk()->assertSee('Ainda não encontramos uma comunidade.')->assertSee('Limpar filtros');
    }

    public function test_pagination_keeps_the_community_filters(): void
    {
        Community::factory()->count(10)->create();

        $this->get('/comunidades?city=Curitiba&type=jovens')->assertOk()
            ->assertSee('city=Curitiba&amp;type=jovens&amp;page=2', false);
    }

    public function test_demo_communities_and_posts_are_not_duplicated_when_seeded_again(): void
    {
        $this->freezeTime();
        $this->seed(CommunitySeeder::class);
        $this->seed(CommunitySeeder::class);

        $this->assertDatabaseCount('communities', 5);
        $this->assertDatabaseCount('community_posts', 5);
    }
}
