<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\CommunityPost;
use App\Models\User;
use Database\Seeders\PhotoDemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostPhotoTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_private_photo_directories_allow_the_shared_server_group_to_read_files(): void
    {
        if (DIRECTORY_SEPARATOR !== '/') {
            $this->markTestSkipped('Permissões POSIX são verificadas em sistemas Unix.');
        }
        $root = storage_path('framework/testing/photo-permissions-'.Str::uuid());
        $disk = Storage::build([...config('filesystems.disks.local'), 'root' => $root]);

        try {
            $disk->put('post-photos/probe.jpg', 'probe');
            clearstatcache();
            $this->assertSame(0050, fileperms($root.'/post-photos') & 0050);
            $this->assertSame(0040, fileperms($root.'/post-photos/probe.jpg') & 0040);
            $this->assertSame(0000, fileperms($root.'/post-photos/probe.jpg') & 0007);
        } finally {
            $disk->deleteDirectory('post-photos');
            if (is_dir($root)) {
                rmdir($root);
            }
        }
    }

    public function test_photo_only_post_is_stored_privately_reencoded_and_served_with_no_cache(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['public_profile' => true]);

        $this->actingAs($user)->post(route('posts.store'), ['audience' => 'public', 'photo' => UploadedFile::fake()->image('foto.png', 1800, 1200)])->assertRedirect();

        $post = CommunityPost::first();
        Storage::disk('local')->assertExists($post->photo_path);
        $this->assertSame('', $post->body);
        $this->assertStringEndsWith('.jpg', $post->photo_path);
        $info = getimagesize(Storage::disk('local')->path($post->photo_path));
        $this->assertSame(1600, $info[0]);
        $this->assertSame('image/jpeg', $info['mime']);
        $this->get(route('posts.photo', $post))->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    }

    public function test_private_photo_is_not_available_by_direct_url_to_others(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['public_profile' => true]);
        $this->actingAs($user)->post(route('posts.store'), ['audience' => 'private', 'photo' => UploadedFile::fake()->image('foto.jpg')])->assertRedirect();
        $post = CommunityPost::first();

        $this->get(route('posts.photo', $post))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('posts.photo', $post))->assertNotFound();
        $post->update(['audience' => 'public']);
        $this->get(route('posts.photo', $post))->assertOk();
        $user->update(['public_profile' => false]);
        $this->get(route('posts.photo', $post))->assertNotFound();
    }

    public function test_membership_revocation_removes_photo_access(): void
    {
        Storage::fake('local');
        $community = Community::factory()->create();
        $entry = CommunityMembership::factory()->for($community)->create();
        $this->actingAs($entry->user)->post(route('posts.store'), ['community_id' => $community->id, 'audience' => 'members', 'photo' => UploadedFile::fake()->image('foto.jpg')])->assertRedirect();
        $post = CommunityPost::first();

        $this->get(route('posts.photo', $post))->assertOk();
        $entry->delete();
        $this->get(route('posts.photo', $post))->assertNotFound();
    }

    public function test_invalid_format_oversized_and_oversized_dimensions_are_rejected(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        foreach ([UploadedFile::fake()->create('arquivo.svg', 1, 'image/svg+xml'), UploadedFile::fake()->image('grande.jpg')->size(5121), UploadedFile::fake()->image('larga.jpg', 3001, 10)] as $photo) {
            $this->post(route('posts.store'), ['body' => 'Texto', 'audience' => 'private', 'photo' => $photo])->assertSessionHasErrors('photo');
        }

        $this->assertDatabaseCount('community_posts', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_delete_removes_photo_and_cannot_be_done_by_another_user(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('posts.store'), ['audience' => 'private', 'photo' => UploadedFile::fake()->image('foto.jpg')])->assertRedirect();
        $post = CommunityPost::first();
        $path = $post->photo_path;

        $this->actingAs(User::factory()->create())->delete(route('posts.destroy', $post))->assertForbidden();
        Storage::disk('local')->assertExists($path);
        $this->actingAs($user)->delete(route('posts.destroy', $post))->assertRedirect();
        Storage::disk('local')->assertMissing($path);
        $this->assertModelMissing($post);
    }

    public function test_posts_without_photo_return_not_found_for_photo_route(): void
    {
        $post = CommunityPost::factory()->create();

        $this->get(route('posts.photo', $post))->assertNotFound();
    }

    public function test_photo_seeder_is_repeatable_and_includes_private_samples(): void
    {
        Storage::fake('local');
        User::factory()->create(['email' => 'membro@kanaa.test', 'public_profile' => true]);

        $this->seed(PhotoDemoSeeder::class);
        $this->seed(PhotoDemoSeeder::class);

        $this->assertDatabaseCount('community_posts', 4);
        $this->assertCount(4, Storage::disk('local')->allFiles());
        $this->assertDatabaseHas('community_posts', ['audience' => 'private']);
        $this->get('/')->assertOk()->assertSee('Imagem fictícia gerada por IA')->assertSee('/foto');
    }
}
