<?php

namespace Database\Seeders;

use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\CommunityPost;
use App\Models\User;
use App\Support\DemoSeederSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SocialDemoSeeder extends Seeder
{
    public function run(): void
    {
        DemoSeederSettings::ensureAllowed();
        $this->call([ChurchSeeder::class, CommunitySeeder::class]);
        $firstNames = ['Rafael', 'Camila', 'Gabriel', 'Juliana', 'Daniel', 'Fernanda', 'André', 'Priscila', 'Tiago', 'Raquel'];
        $lastNames = ['Martins', 'Oliveira', 'Santos', 'Ribeiro', 'Carvalho', 'Ferreira'];
        $password = Hash::make(DemoSeederSettings::password());
        $cities = ['Curitiba', 'São Paulo', 'Belo Horizonte', 'Recife', 'Joinville', 'Londrina'];
        $bios = ['A fé se fortalece quando caminhamos juntos. Gosto de música e de boas conversas.', 'Aprendendo a servir com os dons que recebi. Por aqui compartilho reflexões e descobertas.', 'Leitura, café e comunidade. Sempre aberto a aprender algo novo.', 'Quero fazer parte de uma comunidade que cuida das pessoas e da cidade.'];
        $texts = ['Hoje reservei um tempo para agradecer pelas pequenas coisas. Qual foi a sua boa notícia da semana?', 'Uma conversa atenta pode transformar o dia de alguém. Que a gente saiba ouvir com carinho.', 'Compartilhando uma reflexão: a esperança também se cultiva nas escolhas simples do dia a dia.', 'Quem mais gosta de estudar a Bíblia com um café por perto? Vamos compartilhar nossas leituras.', 'Servir ao próximo começa perto de nós. Tenho aprendido muito participando da comunidade.', 'A música fez parte do meu momento de oração hoje. Como você costuma separar esse tempo?'];
        $users = collect();
        foreach (range(1, 60) as $index) {
            $email = match ($index) {
                1 => 'membro@kanaa.test', 2 => 'gestor@kanaa.test', 3 => 'pendente@kanaa.test', 4 => 'privado@kanaa.test', 5 => 'recusado@kanaa.test', default => 'pessoa'.$index.'@kanaa.test',
            };
            $user = User::firstOrNew(['email' => $email]);
            $user->forceFill(['name' => match ($index) {
                1 => 'Ana Souza', 2 => 'Lucas Mendes', 3 => 'Mariana Alves', 4 => 'Pedro Lima', 5 => 'Beatriz Costa', default => $firstNames[($index - 1) % 10].' '.$lastNames[intdiv($index - 1, 10)]
            }, 'password' => $password, 'email_verified_at' => now(), 'bio' => $bios[($index - 1) % 4], 'city' => $cities[($index - 1) % 6], 'public_profile' => $index !== 4 && $index % 10 !== 0])->save();
            $users->push($user);
            foreach (range(1, 4) as $postIndex) {
                CommunityPost::updateOrCreate(['author_id' => $user->id, 'community_id' => null, 'title' => 'Reflexão de demonstração '.$postIndex], ['body' => $texts[($index + $postIndex) % 6], 'audience' => $postIndex === 4 ? 'private' : 'public', 'status' => 'published', 'published_at' => now()->subHours($index + $postIndex)]);
            }
        }
        $types = array_keys(Community::TYPES);
        $names = ['Café com Propósito', 'Louvor em Casa', 'Servir na Cidade', 'Jovens em Movimento', 'Leitura e Esperança', 'Caminho de Oração', 'Arte e Fé', 'Amigos da Palavra', 'Cuidar do Bairro', 'Famílias em Comunhão', 'Música que Aproxima', 'Semeando Esperança', 'Conversa de Domingo', 'Trilha da Fé', 'Aprendendo Juntos'];
        foreach ($names as $index => $name) {
            Community::updateOrCreate(['slug' => 'demo-'.Str::slug($name)], ['name' => $name, 'description' => 'Comunidade fictícia de demonstração. Um espaço para compartilhar a fé, cultivar amizades e servir juntos.', 'type' => $types[$index % 5], 'city' => $cities[$index % 6], 'state' => ['PR', 'SP', 'MG', 'PE', 'SC', 'PR'][$index % 6], 'visibility' => $index % 3 === 0 ? 'approval' : 'public', 'status' => 'published']);
        }
        $slugs = collect($names)->map(fn (string $name): string => 'demo-'.Str::slug($name))->merge(['jovens-do-caminho', 'maos-que-servem', 'biblia-e-cafe', 'entre-salmos', 'tempo-de-oracao']);
        $communities = Community::whereIn('slug', $slugs)->orderBy('id')->get();
        foreach ($communities as $groupIndex => $community) {
            foreach ($users as $index => $user) {
                if ($index > 4 && ($index + $groupIndex) % 3 !== 0) {
                    continue;
                }
                $membership = CommunityMembership::firstOrNew(['community_id' => $community->id, 'user_id' => $user->id]);
                $membership->role = $index === 1 ? 'manager' : 'member';
                $membership->status = match ($index) {
                    2 => 'pending', 4 => 'rejected', default => 'active'
                };
                $membership->save();
            }
            foreach (range(1, 6) as $postIndex) {
                CommunityPost::updateOrCreate(['community_id' => $community->id, 'title' => 'Conversa da comunidade '.$postIndex], ['author_id' => $users[$postIndex % 2]->id, 'body' => $texts[($groupIndex + $postIndex) % 6], 'audience' => $postIndex % 3 === 0 ? 'members' : 'public', 'status' => 'published', 'published_at' => now()->subHours($postIndex + $groupIndex)]);
            }
        }
        $this->call(InteractionDemoSeeder::class);
    }
}
