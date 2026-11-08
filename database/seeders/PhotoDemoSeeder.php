<?php

namespace Database\Seeders;

use App\Models\CommunityPost;
use App\Models\User;
use App\Support\DemoSeederSettings;
use App\Support\PostPhotoStorage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class PhotoDemoSeeder extends Seeder
{
    public function run(): void
    {
        DemoSeederSettings::ensureAllowed();
        $users = User::whereIn('email', ['membro@kanaa.test', 'gestor@kanaa.test', 'privado@kanaa.test'])->get();
        $photos = app(PostPhotoStorage::class);
        foreach ($users as $user) {
            foreach (['cafe-comunidade' => 'Um café, boas conversas e a Palavra aberta.', 'voluntariado' => 'Servir juntos também é viver em comunidade.'] as $file => $body) {
                foreach (['public', 'private'] as $audience) {
                    $post = CommunityPost::firstOrNew(['author_id' => $user->id, 'community_id' => null, 'title' => 'Foto de demonstração: '.$file.' · '.$audience]);
                    $post->fill(['body' => $body.' Imagem fictícia gerada por IA para demonstração.', 'audience' => $audience, 'status' => 'published', 'published_at' => now()->subMinutes($audience === 'public' ? 5 : 10)]);
                    if (! $post->photo_path || ! Storage::disk('local')->exists($post->photo_path)) {
                        $post->photo_path = $photos->store(database_path('seeders/assets/'.$file.'.png'));
                    }
                    $post->save();
                }
            }
        }
    }
}
