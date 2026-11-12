<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SingleProfileFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'birth_date' => '1995-01-01', 'about' => 'Fé, família e boas conversas.', 'city' => 'Curitiba', 'affinities' => ['fé', 'voluntariado'], 'active' => true, 'consented_at' => now()];
    }
}
