<?php

namespace Database\Factories;

use App\Models\SingleMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

class SingleContactFactory extends Factory
{
    public function definition(): array
    {
        return ['single_match_id' => SingleMatch::factory(), 'user_id' => fn (array $attributes) => SingleMatch::findOrFail($attributes['single_match_id'])->low_user_id, 'channel' => 'email', 'value' => 'ficticio@example.invalid', 'consented_at' => now()];
    }
}
