<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class InfiniteFeedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_home_loads_next_posts_without_duplicates_when_new_posts_arrive(): void
    {
        $this->freezeTime();
        CommunityPost::factory()->count(10)->create();

        $first = $this->get('/', ['X-Kanaa-Feed' => 'posts'])->assertOk()->assertJsonStructure(['html', 'next'])->assertHeader('Cache-Control', 'no-store, private');
        preg_match_all('/id="post-(\d+)"/', $first->json('html'), $firstIds);
        $this->assertCount(8, $firstIds[1]);
        CommunityPost::factory()->create(['title' => 'Chegou agora']);
        $next = $this->get($first->json('next'), ['X-Kanaa-Feed' => 'posts'])->assertOk()->assertJsonPath('next', null);
        preg_match_all('/id="post-(\d+)"/', $next->json('html'), $nextIds);

        $this->assertCount(2, $nextIds[1]);
        $this->assertSame([], array_intersect($firstIds[1], $nextIds[1]));
        $this->assertStringNotContainsString('Chegou agora', $next->json('html'));
        $this->assertStringNotContainsString('<!DOCTYPE', $next->json('html'));
    }

    public function test_home_has_a_working_next_link_without_javascript(): void
    {
        CommunityPost::factory()->count(9)->create();

        $this->get('/')->assertOk()->assertSee('data-infinite-feed', false)->assertSee('Carregar mais')->assertSee('cursor=');
    }

    public function test_empty_feed_has_no_next_page(): void
    {
        $this->get('/', ['X-Kanaa-Feed' => 'posts'])->assertOk()->assertJsonPath('html', '')->assertJsonPath('next', null);
        $this->get('/')->assertOk()->assertSee('Ainda não há publicações para mostrar.')->assertDontSee('data-feed-next', false);
    }

    public function test_profile_continuation_remains_scoped_to_author_and_public_posts(): void
    {
        $this->freezeTime();
        $author = User::factory()->create(['public_profile' => true]);
        CommunityPost::factory()->count(13)->create(['author_id' => $author->id, 'community_id' => null]);
        CommunityPost::factory()->create(['author_id' => $author->id, 'community_id' => null, 'audience' => 'private', 'body' => 'Meu diário secreto']);
        CommunityPost::factory()->create(['body' => 'Post de outra pessoa']);

        $first = $this->get(route('people.show', $author), ['X-Kanaa-Feed' => 'posts'])->assertOk();
        $next = $this->get($first->json('next'), ['X-Kanaa-Feed' => 'posts'])->assertOk()->assertJsonPath('next', null);

        $this->assertStringNotContainsString('Meu diário secreto', $first->json('html').$next->json('html'));
        $this->assertStringNotContainsString('Post de outra pessoa', $first->json('html').$next->json('html'));
        $author->update(['public_profile' => false]);
        $this->get($first->json('next'), ['X-Kanaa-Feed' => 'posts'])->assertNotFound();
    }

    public function test_membership_access_is_checked_again_for_each_batch(): void
    {
        $this->freezeTime();
        $community = Community::factory()->create();
        $membership = CommunityMembership::factory()->for($community)->create();
        CommunityPost::factory()->for($community)->count(9)->create(['audience' => 'members', 'body' => 'Conversa reservada']);

        $first = $this->actingAs($membership->user)->get(route('communities.show', $community->slug), ['X-Kanaa-Feed' => 'posts'])->assertOk();
        $this->assertStringContainsString('Conversa reservada', $first->json('html'));
        $membership->delete();
        $this->get($first->json('next'), ['X-Kanaa-Feed' => 'posts'])->assertOk()->assertJsonPath('html', '')->assertJsonPath('next', null);
    }

    public function test_community_continuation_does_not_mix_other_groups(): void
    {
        $this->freezeTime();
        $community = Community::factory()->create();
        CommunityPost::factory()->for($community)->count(9)->create(['title' => 'Nosso grupo']);
        CommunityPost::factory()->create(['title' => 'Outro grupo']);

        $first = $this->get(route('communities.show', $community->slug), ['X-Kanaa-Feed' => 'posts'])->assertOk();
        $next = $this->get($first->json('next'), ['X-Kanaa-Feed' => 'posts'])->assertOk()->assertJsonPath('next', null);

        $this->assertStringContainsString('Nosso grupo', $next->json('html'));
        $this->assertStringNotContainsString('Outro grupo', $next->json('html'));
    }
}
