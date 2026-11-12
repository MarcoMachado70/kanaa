<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SingleMatchFactory extends Factory
{
    public function definition(): array
    {
        return ['low_user_id' => User::factory(), 'high_user_id' => User::factory(), 'status' => 'active'];
    }
}
