<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CommunityFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->company(), 'slug' => fake()->unique()->slug(), 'description' => fake()->paragraph(), 'type' => 'jovens', 'city' => 'Curitiba', 'state' => 'PR', 'visibility' => 'public', 'status' => 'published'];
    }
}
