<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\CommunityPost;
use App\Models\PostComment;
use App\Models\PostLike;
use App\Models\User;
use Database\Seeders\InteractionDemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PostInteractionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_likes_are_idempotent_and_unlike_only_removes_the_current_users_like(): void
    {
        $post = CommunityPost::factory()->create();
        $user = User::factory()->create();
        $other = PostLike::factory()->for($post, 'post')->create();

        $this->actingAs($user)->put(route('posts.like', $post))->assertRedirect(route('posts.show', $post));
        $this->put(route('posts.like', $post))->assertRedirect();
        $this->assertDatabaseCount('post_likes', 2);
        $this->get(route('posts.show', $post))->assertOk()->assertSee('Curtiu');
        $this->delete(route('posts.unlike', $post))->assertRedirect();
        $this->assertDatabaseCount('post_likes', 1);
        $this->assertModelExists($other);
    }

    public function test_comments_use_authenticated_author_escape_content_and_hide_email(): void
    {
        $post = CommunityPost::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('comments.store', $post), ['body' => '<script>alert(1)</script>', 'user_id' => 999])->assertRedirect();

        $this->assertDatabaseHas('post_comments', ['user_id' => $user->id, 'community_post_id' => $post->id]);
        $this->get(route('posts.show', $post))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertDontSee($user->email);
    }

    public function test_only_comment_author_can_delete_and_parent_is_checked(): void
    {
        $comment = PostComment::factory()->create();
        $otherPost = CommunityPost::factory()->create();

        $this->actingAs(User::factory()->create())->delete(route('comments.destroy', [$comment->post, $comment->id]))->assertForbidden();
        $this->actingAs($comment->user)->delete(route('comments.destroy', [$otherPost, $comment->id]))->assertNotFound();
        $this->assertModelExists($comment);
        $this->delete(route('comments.destroy', [$comment->post, $comment->id]))->assertRedirect();
        $this->assertModelMissing($comment);
    }

    public function test_pending_and_former_members_cannot_access_member_conversations(): void
    {
        $community = Community::factory()->create();
        $entry = CommunityMembership::factory()->for($community)->create();
        $post = CommunityPost::factory()->for($community)->create(['audience' => 'members']);
        PostComment::factory()->for($post, 'post')->create(['body' => 'Conversa reservada']);

        $this->actingAs($entry->user)->get(route('posts.show', $post))->assertOk()->assertSee('Conversa reservada');
        $entry->update(['status' => 'pending']);
        $this->get(route('posts.show', $post))->assertNotFound();
        $this->put(route('posts.like', $post))->assertNotFound();
        $this->post(route('comments.store', $post), ['body' => 'Não pode'])->assertNotFound();
        $entry->delete();
        $this->get(route('posts.show', $post))->assertNotFound();
        $this->assertDatabaseCount('post_likes', 0);
        $this->assertDatabaseCount('post_comments', 1);
    }

    public function test_private_profile_and_unpublished_posts_cannot_be_accessed_directly(): void
    {
        $author = User::factory()->create(['public_profile' => false]);
        $personal = CommunityPost::factory()->create(['community_id' => null, 'author_id' => $author->id]);
        $draft = CommunityPost::factory()->create(['status' => 'draft']);
        $future = CommunityPost::factory()->create(['published_at' => now()->addDay()]);

        foreach ([$personal, $draft, $future] as $post) {
            $this->get(route('posts.show', $post))->assertNotFound();
        }
        $this->actingAs($author)->get(route('posts.show', $personal))->assertOk();
    }

    public function test_comment_validation_and_guest_authentication(): void
    {
        $post = CommunityPost::factory()->create();

        $this->post(route('comments.store', $post), ['body' => 'Oi'])->assertRedirect(route('login'));
        $this->put(route('posts.like', $post))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->post(route('comments.store', $post), ['body' => ' '])->assertSessionHasErrors(['body' => 'Escreva um comentário.']);
        $this->post(route('comments.store', $post), ['body' => str_repeat('a', 2001)])->assertSessionHasErrors(['body' => 'Use até 2000 caracteres no comentário.']);
        $this->assertDatabaseCount('post_comments', 0);
    }

    public function test_comments_are_paginated_and_post_deletion_removes_interactions(): void
    {
        $post = CommunityPost::factory()->create();
        PostComment::factory()->for($post, 'post')->count(21)->create();
        PostLike::factory()->for($post, 'post')->create();

        $this->get(route('posts.show', $post))->assertOk()->assertViewHas('comments', fn ($comments): bool => $comments->total() === 21 && $comments->count() === 20);
        $post->delete();

        $this->assertDatabaseCount('post_comments', 0);
        $this->assertDatabaseCount('post_likes', 0);
    }

    public function test_demo_interactions_are_repeatable(): void
    {
        $user = User::factory()->create(['email' => 'membro@kanaa.test', 'public_profile' => true]);
        CommunityPost::factory()->create(['author_id' => $user->id, 'community_id' => null]);

        $this->seed(InteractionDemoSeeder::class);
        $this->seed(InteractionDemoSeeder::class);

        $this->assertDatabaseCount('post_likes', 1);
        $this->assertDatabaseCount('post_comments', 2);
    }
}
