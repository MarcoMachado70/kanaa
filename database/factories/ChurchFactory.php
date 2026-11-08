<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ChurchFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => 'Comunidade '.fake()->company(), 'slug' => fake()->unique()->slug(), 'denomination' => 'Batista', 'description' => fake()->paragraph(), 'status' => 'published'];
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => 'suspended']);
    }
}
