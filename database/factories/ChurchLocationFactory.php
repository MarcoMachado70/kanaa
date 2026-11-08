<?php

namespace Database\Factories;

use App\Models\Church;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChurchLocationFactory extends Factory
{
    public function definition(): array
    {
        return ['church_id' => Church::factory(), 'name' => 'Sede', 'city' => 'Curitiba', 'state' => 'PR', 'neighborhood' => 'Centro', 'address' => fake()->streetAddress(), 'regular_schedule' => 'Domingo, às 10h e 18h', 'welcomes_visitors' => true];
    }
}
