<?php

namespace Tests\Feature;

use App\Models\SingleBlock;
use App\Models\SingleContact;
use App\Models\SingleInterest;
use App\Models\SingleMatch;
use App\Models\SingleProfile;
use App\Models\User;
use Database\Seeders\SinglesDemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SinglesTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function matchFor(SingleProfile $a, SingleProfile $b): SingleMatch
    {
        return SingleMatch::factory()->create(['low_user_id' => min($a->user_id, $b->user_id), 'high_user_id' => max($a->user_id, $b->user_id)]);
    }

    public function test_opt_in_requires_adulthood_and_explicit_consent_without_changing_general_profile(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['public_profile' => false]);
        $data = ['birth_date' => now()->subYears(17)->toDateString(), 'about' => 'Fé e família', 'city' => 'Curitiba', 'affinities' => ['fé']];

        $this->actingAs($user)->put(route('singles.save'), $data)->assertSessionHasErrors(['birth_date', 'consent']);
        $this->assertDatabaseCount('single_profiles', 0);
        $data['birth_date'] = now()->subYears(18)->toDateString();
        $data['consent'] = '1';
        $this->put(route('singles.save'), $data)->assertRedirect();
        $this->assertDatabaseHas('single_profiles', ['user_id' => $user->id, 'active' => true]);
        $this->assertFalse($user->fresh()->public_profile);
    }

    public function test_only_eligible_participants_can_discover_profiles_or_send_interest(): void
    {
        $target = SingleProfile::factory()->create(['about' => 'Texto exclusivo da área']);
        $inactive = User::factory()->create();

        $this->get(route('singles.index'))->assertRedirect(route('login'));
        $this->actingAs($inactive)->get(route('singles.index'))->assertOk()->assertDontSee('Texto exclusivo da área');
        $this->put(route('singles.interest', $target))->assertNotFound();
        $this->assertDatabaseCount('single_interests', 0);
    }

    public function test_discovery_hides_birth_date_contacts_paused_minor_and_blocked_profiles(): void
    {
        $this->freezeTime();
        $me = SingleProfile::factory()->create();
        $target = SingleProfile::factory()->create(['about' => 'Perfil visível']);
        SingleProfile::factory()->create(['about' => 'Perfil pausado', 'active' => false]);
        SingleProfile::factory()->create(['about' => 'Perfil menor', 'birth_date' => now()->subYears(17)]);
        $blocked = SingleProfile::factory()->create(['about' => 'Perfil bloqueado']);
        SingleBlock::factory()->create(['from_user_id' => $blocked->user_id, 'to_user_id' => $me->user_id]);

        $this->actingAs($me->user)->get(route('singles.index'))->assertOk()->assertSee('Perfil visível')->assertDontSee('Perfil pausado')->assertDontSee('Perfil menor')->assertDontSee('Perfil bloqueado')->assertDontSee($target->user->email);
        $this->assertArrayNotHasKey('birth_date', $target->toArray());
    }

    public function test_interests_are_private_idempotent_and_reciprocity_creates_one_match(): void
    {
        $a = SingleProfile::factory()->create();
        $b = SingleProfile::factory()->create();

        $this->actingAs($a->user)->put(route('singles.interest', $b))->assertRedirect();
        $this->put(route('singles.interest', $b))->assertRedirect();
        $this->assertDatabaseCount('single_interests', 1);
        $this->assertDatabaseCount('single_matches', 0);
        $this->actingAs($b->user)->put(route('singles.interest', $a))->assertRedirect();
        $this->put(route('singles.interest', $a))->assertRedirect();
        $this->assertDatabaseCount('single_matches', 1);
        $this->assertDatabaseCount('single_contacts', 0);
    }

    public function test_contact_needs_both_consents_is_encrypted_and_never_shown_on_generic_screens(): void
    {
        $a = SingleProfile::factory()->create();
        $b = SingleProfile::factory()->create();
        $match = $this->matchFor($a, $b);
        $payload = ['channel' => 'email', 'value' => 'apenas-no-contato@example.invalid', 'consent' => '1'];

        $this->actingAs($a->user)->put(route('singles.authorize', $match), $payload)->assertRedirect();
        $this->get(route('singles.contact', $match))->assertConflict();
        $this->actingAs($b->user)->put(route('singles.authorize', $match), ['channel' => 'email', 'value' => 'outra-pessoa@example.invalid', 'consent' => '1'])->assertRedirect();
        $this->get(route('singles.contact', $match))->assertOk()->assertSee($payload['value'])->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        $this->get(route('singles.match', $match))->assertOk()->assertDontSee($payload['value']);
        $this->get(route('singles.index'))->assertOk()->assertDontSee($payload['value']);
        $stored = DB::table('single_contacts')->where('user_id', $a->user_id)->value('value');
        $this->assertStringNotContainsString($payload['value'], $stored);
        $this->assertArrayNotHasKey('value', SingleContact::first()->toArray());
        $this->delete(route('singles.revoke', $match))->assertRedirect();
        $this->get(route('singles.contact', $match))->assertConflict();
    }

    public function test_outsiders_cannot_access_or_authorize_someone_elses_match(): void
    {
        $a = SingleProfile::factory()->create();
        $b = SingleProfile::factory()->create();
        $outsider = SingleProfile::factory()->create();
        $match = $this->matchFor($a, $b);

        $this->actingAs($outsider->user)->get(route('singles.match', $match))->assertNotFound();
        $this->get(route('singles.contact', $match))->assertNotFound();
        $this->put(route('singles.authorize', $match), ['channel' => 'email', 'value' => 'test@example.invalid', 'consent' => '1'])->assertNotFound();
        $this->delete(route('singles.end', $match))->assertNotFound();
        $this->assertDatabaseCount('single_contacts', 0);
    }

    public function test_pause_clears_interests_and_contacts_and_does_not_restore_them_on_resume(): void
    {
        $a = SingleProfile::factory()->create();
        $b = SingleProfile::factory()->create();
        $match = $this->matchFor($a, $b);
        SingleInterest::factory()->create(['from_user_id' => $a->user_id, 'to_user_id' => $b->user_id]);
        SingleContact::factory()->create(['single_match_id' => $match->id, 'user_id' => $a->user_id]);

        $this->actingAs($a->user)->delete(route('singles.pause'))->assertRedirect();

        $this->assertDatabaseHas('single_profiles', ['id' => $a->id, 'active' => false]);
        $this->assertDatabaseHas('single_matches', ['id' => $match->id, 'status' => 'ended']);
        $this->assertDatabaseCount('single_interests', 0);
        $this->assertDatabaseCount('single_contacts', 0);
        $a->update(['active' => true]);
        $this->get(route('singles.match', $match))->assertNotFound();
    }

    public function test_block_is_bilateral_and_ends_matches_and_contact(): void
    {
        $a = SingleProfile::factory()->create();
        $b = SingleProfile::factory()->create();
        $match = $this->matchFor($a, $b);
        SingleContact::factory()->create(['single_match_id' => $match->id, 'user_id' => $a->user_id]);

        $this->actingAs($a->user)->put(route('singles.block', $b))->assertRedirect();
        $this->actingAs($b->user)->put(route('singles.interest', $a))->assertNotFound();
        $this->get(route('singles.match', $match))->assertNotFound();
        $this->assertDatabaseCount('single_contacts', 0);
        $this->assertDatabaseHas('single_matches', ['id' => $match->id, 'status' => 'ended']);
    }

    public function test_reports_are_registered_without_exposing_the_reason_to_the_target(): void
    {
        $a = SingleProfile::factory()->create();
        $b = SingleProfile::factory()->create();

        $this->actingAs($a->user)->post(route('singles.report', $b), ['reason' => 'Relato privado de comportamento inadequado.'])->assertRedirect();

        $this->assertDatabaseHas('single_reports', ['from_user_id' => $a->user_id, 'to_user_id' => $b->user_id, 'status' => 'pending']);
        $this->actingAs($b->user)->get(route('singles.index'))->assertOk()->assertDontSee('Relato privado de comportamento inadequado.');
    }

    public function test_end_match_requires_new_interests_and_revokes_authorizations(): void
    {
        $a = SingleProfile::factory()->create();
        $b = SingleProfile::factory()->create();
        $match = $this->matchFor($a, $b);
        SingleContact::factory()->create(['single_match_id' => $match->id, 'user_id' => $a->user_id]);

        $this->actingAs($a->user)->delete(route('singles.end', $match))->assertRedirect();
        $this->assertDatabaseCount('single_contacts', 0);
        $this->put(route('singles.interest', $b))->assertRedirect();
        $this->assertDatabaseHas('single_matches', ['id' => $match->id, 'status' => 'ended']);
        $this->actingAs($b->user)->put(route('singles.interest', $a))->assertRedirect();
        $this->assertDatabaseCount('single_matches', 1);
        $this->assertDatabaseHas('single_matches', ['id' => $match->id, 'status' => 'active']);
        $this->assertDatabaseCount('single_contacts', 0);
    }

    public function test_seed_only_activates_fictitious_accounts_and_is_repeatable(): void
    {
        User::factory()->create(['email' => 'membro@kanaa.test']);
        User::factory()->create(['email' => 'gestor@kanaa.test']);
        User::factory()->create(['email' => 'pessoa-real@example.com']);

        $this->seed(SinglesDemoSeeder::class);
        $this->seed(SinglesDemoSeeder::class);

        $this->assertDatabaseCount('single_profiles', 2);
        $this->assertDatabaseCount('single_matches', 1);
        $this->assertDatabaseCount('single_contacts', 0);
    }

    public function test_withdrawing_interest_does_not_allow_a_one_sided_match(): void
    {
        $a = SingleProfile::factory()->create();
        $b = SingleProfile::factory()->create();
        $this->actingAs($a->user)->put(route('singles.interest', $b))->assertRedirect();

        $this->delete(route('singles.cancel', $b))->assertRedirect();
        $this->actingAs($b->user)->put(route('singles.interest', $a))->assertRedirect();

        $this->assertDatabaseCount('single_matches', 0);
        $this->assertDatabaseCount('single_interests', 1);
    }

    public function test_contact_authorization_rejects_missing_consent_and_invalid_channels(): void
    {
        $a = SingleProfile::factory()->create();
        $b = SingleProfile::factory()->create();
        $match = $this->matchFor($a, $b);

        $this->actingAs($a->user)->put(route('singles.authorize', $match), ['channel' => 'email', 'value' => 'not-an-email'])->assertSessionHasErrors(['value', 'consent']);
        $this->put(route('singles.authorize', $match), ['channel' => 'unapproved', 'value' => 'arbitrary', 'consent' => '1'])->assertSessionHasErrors('channel');

        $this->assertDatabaseCount('single_contacts', 0);
    }
}
