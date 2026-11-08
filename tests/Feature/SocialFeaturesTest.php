<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\CommunityPost;
use App\Models\User;
use Database\Seeders\SocialDemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SocialFeaturesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_create_community_and_becomes_its_manager(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/comunidades', ['name' => 'Café e Fé', 'description' => 'Um grupo para aprender juntos.', 'city' => 'Curitiba', 'state' => 'pr', 'type' => 'estudos', 'visibility' => 'approval', 'church_id' => 999])->assertRedirect();

        $community = Community::first();
        $this->assertNull($community->church_id);
        $this->assertDatabaseHas('community_memberships', ['community_id' => $community->id, 'user_id' => $user->id, 'role' => 'manager', 'status' => 'active']);
        $this->get(route('communities.manage', $community))->assertOk();
    }

    public function test_personal_posts_respect_profile_and_post_privacy(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['public_profile' => true]);
        $this->actingAs($user)->post('/publicacoes', ['body' => 'Uma reflexão pública', 'audience' => 'public'])->assertRedirect();
        $this->post('/publicacoes', ['body' => 'Meu diário pessoal', 'audience' => 'private'])->assertRedirect();

        $this->get(route('people.show', $user))->assertOk()->assertSee('Meu diário pessoal');
        $this->actingAs(User::factory()->create())->get('/')->assertOk()->assertSee('Uma reflexão pública')->assertDontSee('Meu diário pessoal');
        $this->get(route('people.show', $user))->assertOk()->assertDontSee('Meu diário pessoal')->assertDontSee($user->email);
        $user->update(['public_profile' => false]);
        $this->get(route('people.show', $user))->assertNotFound();
        $this->get('/')->assertOk()->assertDontSee('Uma reflexão pública');
    }

    public function test_only_active_members_can_post_and_read_member_content(): void
    {
        $this->freezeTime();
        $community = Community::factory()->create();
        $active = CommunityMembership::factory()->for($community)->create();
        $pending = CommunityMembership::factory()->for($community)->create(['status' => 'pending']);

        $this->actingAs($pending->user)->post('/publicacoes', ['body' => 'Não pode publicar', 'audience' => 'public', 'community_id' => $community->id])->assertForbidden();
        $this->actingAs($active->user)->post('/publicacoes', ['body' => 'Conversa interna', 'audience' => 'members', 'community_id' => $community->id])->assertRedirect();
        $this->get(route('communities.show', $community->slug))->assertOk()->assertSee('Conversa interna');
        $this->actingAs($pending->user)->get(route('communities.show', $community->slug))->assertOk()->assertDontSee('Conversa interna');
        $this->get('/')->assertOk()->assertDontSee('Conversa interna');
    }

    public function test_posts_cannot_impersonate_or_delete_another_author(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->post('/publicacoes', ['body' => '<script>alert(1)</script>', 'audience' => 'private', 'author_id' => $other->id])->assertRedirect();

        $post = CommunityPost::first();
        $this->assertSame($user->id, $post->author_id);
        $this->get(route('people.show', $user))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        $this->actingAs($other)->delete(route('posts.destroy', $post))->assertForbidden();
        $this->assertModelExists($post);
        $this->actingAs($user)->delete(route('posts.destroy', $post))->assertRedirect();
        $this->assertModelMissing($post);
    }

    public function test_profile_edit_does_not_change_account_credentials(): void
    {
        $user = User::factory()->create();
        $email = $user->email;

        $this->actingAs($user)->patch('/perfil', ['name' => 'Ana', 'bio' => 'Aprendendo juntos', 'city' => 'Recife', 'public_profile' => '1', 'email' => 'outra@example.com'])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Ana', 'email' => $email, 'city' => 'Recife', 'public_profile' => true]);
    }

    public function test_creation_forms_and_profile_editor_render(): void
    {
        $this->actingAs(User::factory()->create())->get('/comunidades/criar')->assertOk()->assertSee('Comece uma comunidade.');
        $this->get('/perfil/editar')->assertOk()->assertSee('Seu perfil, sua história.');
    }

    public function test_empty_posts_and_guests_are_rejected(): void
    {
        $this->post('/publicacoes', ['body' => 'Texto', 'audience' => 'public'])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->post('/publicacoes', ['body' => '', 'audience' => 'public'])->assertSessionHasErrors('body');
        $this->assertDatabaseCount('community_posts', 0);
    }

    public function test_sample_data_is_large_repeatable_and_has_distinct_participation_types(): void
    {
        $this->freezeTime();
        $this->seed(SocialDemoSeeder::class);
        $this->seed(SocialDemoSeeder::class);

        $this->assertDatabaseCount('users', 60);
        $this->assertDatabaseCount('communities', 20);
        $this->assertDatabaseCount('community_posts', 365);
        $this->assertDatabaseHas('community_memberships', ['status' => 'pending']);
        $this->assertDatabaseHas('community_memberships', ['status' => 'rejected']);
        $this->assertDatabaseHas('community_memberships', ['role' => 'manager']);
        $this->assertDatabaseHas('users', ['email' => 'privado@kanaa.test', 'public_profile' => false]);
    }
}
