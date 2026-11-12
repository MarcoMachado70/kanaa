<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SingleBlockFactory extends Factory
{
    public function definition(): array
    {
        return ['from_user_id' => User::factory(), 'to_user_id' => User::factory()];
    }
}
