<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ParticipationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registration_creates_an_authenticated_account_with_hashed_password(): void
    {
        $this->post('/cadastro', ['name' => 'Ana', 'email' => 'ANA@example.com', 'password' => 'uma-senha-longa', 'password_confirmation' => 'uma-senha-longa'])
            ->assertRedirect(route('profile'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'ana@example.com']);
        $this->assertTrue(Hash::check('uma-senha-longa', User::first()->password));
    }

    public function test_registration_rejects_mismatched_passwords(): void
    {
        $this->from('/cadastro')->post('/cadastro', ['name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'uma-senha-longa', 'password_confirmation' => 'outra-senha-longa'])
            ->assertSessionHasErrors(['password' => 'As senhas precisam ser iguais.']);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_short_passwords_and_duplicate_normalized_email(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->post('/cadastro', ['name' => 'Ana', 'email' => 'ANA@example.com', 'password' => 'curta', 'password_confirmation' => 'curta'])
            ->assertSessionHasErrors(['email' => 'Este e-mail já está cadastrado.', 'password' => 'Use uma senha com pelo menos 12 caracteres.']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_login_and_logout_change_authentication_state(): void
    {
        User::factory()->create(['email' => 'ana@example.com', 'password' => 'uma-senha-longa']);

        $this->post('/entrar', ['email' => 'ANA@example.com', 'password' => 'uma-senha-longa'])->assertRedirect(route('profile'));
        $this->assertAuthenticated();
        $this->post('/sair')->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_bad_credentials_do_not_log_in(): void
    {
        $this->post('/entrar', ['email' => 'naoexiste@example.com', 'password' => 'incorreta'])
            ->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);

        $this->assertGuest();
    }

    public function test_login_attempts_are_limited(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post('/entrar', ['email' => 'naoexiste@example.com', 'password' => 'incorreta']);
        }

        $this->post('/entrar', ['email' => 'naoexiste@example.com', 'password' => 'incorreta'])->assertTooManyRequests();
    }

    public function test_guests_cannot_join_or_access_profile(): void
    {
        $community = Community::factory()->create();

        $this->put(route('memberships.store', $community->slug))->assertRedirect(route('login'));
        $this->get('/perfil')->assertRedirect(route('login'));
        $this->assertDatabaseCount('community_memberships', 0);
    }

    public function test_joining_is_idempotent_and_does_not_accept_a_different_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $community = Community::factory()->create();

        $this->actingAs($user)->put(route('memberships.store', $community->slug), ['user_id' => $other->id])->assertRedirect();
        $this->put(route('memberships.store', $community->slug))->assertRedirect();

        $this->assertDatabaseCount('community_memberships', 1);
        $this->assertDatabaseHas('community_memberships', ['user_id' => $user->id, 'status' => 'active']);
    }

    public function test_approval_group_keeps_request_pending_and_prevents_status_injection(): void
    {
        $community = Community::factory()->create(['visibility' => 'approval']);
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('memberships.store', $community->slug), ['status' => 'active'])->assertRedirect();

        $this->assertDatabaseHas('community_memberships', ['community_id' => $community->id, 'user_id' => $user->id, 'status' => 'pending']);
        $this->get(route('communities.show', $community->slug))->assertOk()->assertSee('Sua solicitação aguarda aprovação.');
    }

    public function test_private_groups_cannot_be_joined_by_direct_url(): void
    {
        $community = Community::factory()->create(['visibility' => 'private']);

        $this->actingAs(User::factory()->create())->put(route('memberships.store', $community->slug))->assertNotFound();

        $this->assertDatabaseCount('community_memberships', 0);
    }

    public function test_profile_and_leaving_only_affect_the_current_user(): void
    {
        $mine = CommunityMembership::factory()->create();
        $other = CommunityMembership::factory()->create();

        $this->actingAs($mine->user)->get('/perfil')->assertOk()->assertSee($mine->community->name)->assertDontSee($other->community->name);
        $this->delete(route('memberships.destroy', $mine->community->slug))->assertRedirect(route('profile'));

        $this->assertModelMissing($mine);
        $this->assertModelExists($other);
    }
}
