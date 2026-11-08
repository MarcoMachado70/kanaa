<?php

namespace Database\Seeders;

use App\Models\CommunityMembership;
use App\Models\CommunityPost;
use App\Models\PostComment;
use App\Models\PostLike;
use App\Models\User;
use App\Support\DemoSeederSettings;
use Illuminate\Database\Seeder;

class InteractionDemoSeeder extends Seeder
{
    public function run(): void
    {
        DemoSeederSettings::ensureAllowed();
        $users = User::where('email', 'like', '%@kanaa.test')->orderBy('id')->get();
        if ($users->isEmpty()) {
            return;
        }
        $posts = CommunityPost::publiclyVisible()->where(function ($query) use ($users): void {
            $query->whereIn('author_id', $users->pluck('id'))->orWhereHas('community', fn ($community) => $community->where('slug', 'like', 'demo-%'));
        })->orderBy('id')->get();
        $phrases = ['Que reflexão bonita! Obrigado por compartilhar.', 'Caminhar juntos faz toda a diferença.', 'Gostei muito dessa ideia. Vamos conversar mais sobre isso!', 'Uma boa lembrança para levar para a semana.'];
        foreach ($posts as $index => $post) {
            foreach (range(0, 2 + $index % 5) as $offset) {
                $user = $users[($index + $offset) % $users->count()];
                if ($post->community_id && ! CommunityMembership::where('community_id', $post->community_id)->where('user_id', $user->id)->where('status', 'active')->exists()) {
                    continue;
                }
                PostLike::firstOrCreate(['community_post_id' => $post->id, 'user_id' => $user->id]);
                if ($offset < 2) {
                    PostComment::firstOrCreate(['community_post_id' => $post->id, 'user_id' => $user->id, 'body' => $phrases[($index + $offset) % 4]]);
                }
            }
        }
    }
}
