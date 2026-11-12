<?php

namespace Tests\Feature;

use App\Models\CommunityPost;
use App\Models\User;
use App\Models\UserFollow;
use Database\Seeders\FollowDemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PeopleConnectionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_discovery_only_returns_matching_public_profiles_without_contact_information(): void
    {
        $me = User::factory()->create();
        $person = User::factory()->create(['name' => 'Ana Recife', 'city' => 'Recife', 'public_profile' => true]);
        User::factory()->create(['name' => 'Ana privada', 'city' => 'Recife', 'public_profile' => false]);
        User::factory()->create(['name' => 'Ana Curitiba', 'city' => 'Curitiba', 'public_profile' => true]);

        $this->actingAs($me)->get('/pessoas?q=Ana&city=Recife')->assertOk()->assertSee('Ana Recife')->assertDontSee('Ana privada')->assertDontSee('Ana Curitiba')->assertDontSee($person->email);
    }

    public function test_follow_is_idempotent_and_cannot_impersonate_another_user(): void
    {
        $me = User::factory()->create();
        $person = User::factory()->create(['public_profile' => true]);

        $this->actingAs($me)->put(route('people.follow', $person), ['follower_id' => $person->id])->assertRedirect();
        $this->put(route('people.follow', $person))->assertRedirect();

        $this->assertDatabaseCount('user_follows', 1);
        $this->assertDatabaseHas('user_follows', ['follower_id' => $me->id, 'followed_id' => $person->id]);
        $this->get(route('people.show', $person))->assertOk()->assertSee('Deixar de seguir');
    }

    public function test_guests_self_follow_and_private_targets_are_refused(): void
    {
        $me = User::factory()->create(['public_profile' => true]);
        $private = User::factory()->create();

        $this->get('/pessoas')->assertRedirect(route('login'));
        $this->put(route('people.follow', $me))->assertRedirect(route('login'));
        $this->actingAs($me)->put(route('people.follow', $me))->assertUnprocessable();
        $this->put(route('people.follow', $private))->assertNotFound();
        $this->assertDatabaseCount('user_follows', 0);
    }

    public function test_unfollow_only_removes_current_users_relationship_even_after_profile_is_private(): void
    {
        $person = User::factory()->create(['public_profile' => true]);
        $mine = UserFollow::factory()->create(['followed_id' => $person->id]);
        $other = UserFollow::factory()->create(['followed_id' => $person->id]);
        $person->update(['public_profile' => false]);

        $this->actingAs(User::findOrFail($mine->follower_id))->delete(route('people.unfollow', $person))->assertRedirect();

        $this->assertModelMissing($mine);
        $this->assertModelExists($other);
    }

    public function test_following_feed_and_cursor_keep_visibility_and_author_filter(): void
    {
        $this->freezeTime();
        $me = User::factory()->create();
        $person = User::factory()->create(['public_profile' => true]);
        UserFollow::factory()->create(['follower_id' => $me->id, 'followed_id' => $person->id]);
        CommunityPost::factory()->count(9)->create(['community_id' => null, 'author_id' => $person->id, 'body' => 'Texto da pessoa seguida']);
        CommunityPost::factory()->create(['community_id' => null, 'author_id' => $person->id, 'body' => 'Diário privado', 'audience' => 'private']);
        CommunityPost::factory()->create(['body' => 'Autor não seguido']);

        $first = $this->actingAs($me)->get('/?feed=following', ['X-Kanaa-Feed' => 'posts'])->assertOk();
        $this->assertStringContainsString('feed=following', $first->json('next'));
        $next = $this->get($first->json('next'), ['X-Kanaa-Feed' => 'posts'])->assertOk()->assertJsonPath('next', null);
        $this->assertStringContainsString('Texto da pessoa seguida', $next->json('html'));
        $this->assertStringNotContainsString('Diário privado', $first->json('html').$next->json('html'));
        $this->assertStringNotContainsString('Autor não seguido', $first->json('html').$next->json('html'));
        $person->update(['public_profile' => false]);
        $this->get('/?feed=following', ['X-Kanaa-Feed' => 'posts'])->assertOk()->assertJsonPath('html', '');
    }

    public function test_following_list_is_private_and_only_contains_visible_followed_profiles(): void
    {
        $me = User::factory()->create();
        $person = User::factory()->create(['public_profile' => true]);
        $other = User::factory()->create(['public_profile' => true]);
        UserFollow::factory()->create(['follower_id' => $me->id, 'followed_id' => $person->id]);

        $this->actingAs($me)->get('/pessoas?tab=following')->assertOk()->assertSee($person->name)->assertDontSee($other->name);
        $this->actingAs($other)->get('/pessoas?tab=following')->assertOk()->assertDontSee($person->name);
    }

    public function test_demo_connections_can_be_repeated_without_duplicates(): void
    {
        User::factory()->create(['email' => 'membro@kanaa.test', 'public_profile' => true]);
        User::factory()->create(['email' => 'gestor@kanaa.test', 'public_profile' => true]);

        $this->seed(FollowDemoSeeder::class);
        $this->seed(FollowDemoSeeder::class);

        $this->assertDatabaseCount('user_follows', 2);
    }
}
