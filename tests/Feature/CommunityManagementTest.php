<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CommunityManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_only_active_managers_of_the_community_can_manage_it(): void
    {
        $community = Community::factory()->create();
        $manager = CommunityMembership::factory()->manager()->for($community)->create();
        $member = CommunityMembership::factory()->for($community)->create();
        $pending = CommunityMembership::factory()->manager()->for($community)->create(['status' => 'pending']);
        $other = CommunityMembership::factory()->manager()->create();

        $this->assertTrue(Gate::forUser($manager->user)->allows('manage', $community));
        foreach ([$member->user, $pending->user, $other->user, User::factory()->create()] as $user) {
            $this->assertFalse(Gate::forUser($user)->allows('manage', $community));
        }
        $community->update(['status' => 'draft']);
        $this->assertFalse(Gate::forUser($manager->user)->allows('manage', $community->fresh()));
    }

    public function test_guests_and_members_cannot_read_the_management_panel_or_review(): void
    {
        $entry = CommunityMembership::factory()->create(['status' => 'pending']);

        $this->get(route('communities.manage', $entry->community))->assertRedirect(route('login'));
        $this->actingAs($entry->user)->get(route('communities.manage', $entry->community))->assertForbidden();
        $this->patch(route('communities.review', [$entry->community, $entry->id]), ['decision' => 'approve'])->assertForbidden();
        $this->assertDatabaseHas('community_memberships', ['id' => $entry->id, 'status' => 'pending']);
    }

    public function test_panel_only_shows_names_of_pending_members_of_its_community(): void
    {
        $manager = CommunityMembership::factory()->manager()->create();
        $pending = CommunityMembership::factory()->for($manager->community)->create(['status' => 'pending']);
        $other = CommunityMembership::factory()->create(['status' => 'pending']);

        $this->actingAs($manager->user)->get(route('communities.manage', $manager->community))->assertOk()
            ->assertSee($pending->user->name)->assertDontSee($pending->user->email)->assertDontSee($other->user->name);
    }

    public function test_approval_records_reviewer_and_repeated_decision_is_a_conflict(): void
    {
        $this->travelTo(now()->startOfSecond());
        $manager = CommunityMembership::factory()->manager()->create();
        $pending = CommunityMembership::factory()->for($manager->community)->create(['status' => 'pending']);

        $this->actingAs($manager->user)->patch(route('communities.review', [$manager->community, $pending->id]), ['decision' => 'approve'])->assertRedirect();

        $this->assertDatabaseHas('community_memberships', ['id' => $pending->id, 'status' => 'active', 'role' => 'member', 'reviewed_by' => $manager->user_id]);
        $this->assertTrue($pending->fresh()->reviewed_at->equalTo(now()));
        $this->patch(route('communities.review', [$manager->community, $pending->id]), ['decision' => 'reject'])->assertConflict();
        $this->assertDatabaseHas('community_memberships', ['id' => $pending->id, 'status' => 'active']);
    }

    public function test_rejection_is_visible_to_the_requester(): void
    {
        $manager = CommunityMembership::factory()->manager()->create();
        $pending = CommunityMembership::factory()->for($manager->community)->create(['status' => 'pending']);

        $this->actingAs($manager->user)->patch(route('communities.review', [$manager->community, $pending->id]), ['decision' => 'reject'])->assertRedirect();

        $this->assertDatabaseHas('community_memberships', ['id' => $pending->id, 'status' => 'rejected']);
        $this->actingAs($pending->user)->get(route('communities.show', $manager->community->slug))->assertOk()->assertSee('Sua solicitação não foi aprovada.');
    }

    public function test_manager_cannot_review_a_request_from_another_community(): void
    {
        $manager = CommunityMembership::factory()->manager()->create();
        $other = CommunityMembership::factory()->create(['status' => 'pending']);

        $this->actingAs($manager->user)->patch(route('communities.review', [$manager->community, $other->id]), ['decision' => 'approve'])->assertNotFound();

        $this->assertDatabaseHas('community_memberships', ['id' => $other->id, 'status' => 'pending']);
    }

    public function test_invalid_decision_does_not_change_the_request(): void
    {
        $manager = CommunityMembership::factory()->manager()->create();
        $entry = CommunityMembership::factory()->for($manager->community)->create(['status' => 'pending']);

        $this->actingAs($manager->user)->patch(route('communities.review', [$manager->community, $entry->id]), ['decision' => 'manager'])->assertSessionHasErrors('decision');

        $this->assertDatabaseHas('community_memberships', ['id' => $entry->id, 'status' => 'pending', 'role' => 'member']);
    }

    public function test_join_request_cannot_grant_management_role(): void
    {
        $community = Community::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('memberships.store', $community->slug), ['role' => 'manager'])->assertRedirect();

        $this->assertDatabaseHas('community_memberships', ['community_id' => $community->id, 'user_id' => $user->id, 'role' => 'member']);
    }

    public function test_operator_command_assigns_existing_account_and_is_idempotent(): void
    {
        $community = Community::factory()->create();
        $user = User::factory()->create();

        $this->artisan('kanaa:assign-manager', ['community' => $community->slug, 'email' => $user->email])->assertSuccessful();
        $this->artisan('kanaa:assign-manager', ['community' => $community->slug, 'email' => $user->email])->assertSuccessful();

        $this->assertDatabaseCount('community_memberships', 1);
        $this->assertDatabaseHas('community_memberships', ['user_id' => $user->id, 'role' => 'manager', 'status' => 'active']);
    }

    public function test_operator_command_does_not_create_missing_accounts(): void
    {
        $community = Community::factory()->create();

        $this->artisan('kanaa:assign-manager', ['community' => $community->slug, 'email' => 'missing@example.com'])->assertFailed();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('community_memberships', 0);
    }
}
