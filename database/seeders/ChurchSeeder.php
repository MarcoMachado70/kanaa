<?php

namespace Database\Seeders;

use App\Models\Church;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ChurchSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        foreach (['Comunidade Esperança' => 'Batista', 'Comunidade do Caminho' => 'Presbiteriana', 'Comunidade Vida Nova' => null] as $name => $denomination) {
            $church = Church::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name.' (demonstração)', 'denomination' => $denomination,
                'description' => 'Comunidade fictícia para desenvolver a Kanaã. Um espaço de encontro, fé e serviço à cidade.', 'status' => 'published',
            ]);
            foreach (['Centro', 'Água Verde'] as $neighborhood) {
                $church->locations()->updateOrCreate(['name' => $neighborhood], [
                    'city' => 'Curitiba', 'state' => 'PR', 'neighborhood' => $neighborhood,
                    'address' => 'Endereço fictício — não utilizar para visitas',
                    'regular_schedule' => 'Domingos às 10h e às 18h',
                    'accessibility' => 'Informação de exemplo: acesso sem degraus.',
                    'visitor_information' => 'Dados de demonstração. Confirme orientações com a igreja antes de visitar.',
                    'welcomes_visitors' => true,
                ]);
            }
        }
    }
}
