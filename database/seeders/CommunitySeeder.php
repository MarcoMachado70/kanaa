<?php

namespace Database\Seeders;

use App\Models\Church;
use App\Models\Community;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CommunitySeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $examples = [
            ['Jovens do Caminho', 'jovens', 'Um lugar para fazer amigos, compartilhar a fé e caminhar junto. Conversas, encontros e novas histórias.', 'Fé que se vive em comunidade', 'Tem espaço para você na roda. Nossa comunidade é feita de encontros, escuta e amizade. Conheça o grupo e descubra o que podemos construir juntos.'],
            ['Mãos que Servem', 'voluntariado', 'Pequenos gestos transformam a nossa cidade. Conecte-se com quem quer cuidar do próximo.', 'Cuidar também é uma forma de amar', 'Uma cesta organizada, uma conversa com calma, um tempo dedicado ao outro. Servir começa nos gestos simples. Que tal conhecer nossa comunidade de voluntariado?'],
            ['Bíblia e Café', 'estudos', 'Um café, a Bíblia aberta e boas conversas. Um espaço acolhedor para aprender e compartilhar.', 'Há perguntas que merecem uma boa conversa', 'Aqui a gente aprende junto. Traga suas perguntas, sua curiosidade e sua vontade de conhecer a Palavra. Todos são bem-vindos para acompanhar nossas reflexões.'],
            ['Entre Salmos', 'musica', 'Música, adoração e criatividade para aproximar pessoas e celebrar a fé.', 'Uma canção pode aproximar pessoas', 'Nossa comunidade reúne quem gosta de música e deseja usar seus dons para servir. Um espaço para aprender, trocar referências e criar juntos.'],
            ['Tempo de Oração', 'oracao', 'Uma pausa na rotina para cultivar a fé e orar em comunidade.', 'Um momento para aquietar o coração', 'Em meio à correria, há valor em parar e ouvir. Este é um convite para cultivar uma rotina de oração e encorajar uns aos outros.'],
        ];
        foreach ($examples as $index => [$name, $type, $description, $title, $body]) {
            $community = Community::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name, 'type' => $type, 'description' => $description,
                'city' => 'Curitiba', 'state' => 'PR', 'visibility' => $index === 0 ? 'approval' : 'public',
                'status' => 'published', 'church_id' => Church::where('slug', 'comunidade-esperanca')->value('id'),
            ]);
            $community->posts()->updateOrCreate(['title' => $title], [
                'body' => $body, 'audience' => 'public', 'status' => 'published', 'published_at' => now()->startOfDay()->subHours($index + 1),
            ]);
        }
    }
}
