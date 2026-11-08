<?php

namespace Database\Factories;

use App\Models\Community;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommunityPostFactory extends Factory
{
    public function definition(): array
    {
        return ['community_id' => Community::factory(), 'title' => fake()->sentence(), 'body' => fake()->paragraph(), 'audience' => 'public', 'status' => 'published', 'published_at' => now()->subHour()];
    }
}
