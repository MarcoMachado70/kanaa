<?php

namespace Database\Factories;

use App\Models\Community;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommunityMembershipFactory extends Factory
{
    public function manager(): static
    {
        return $this->state(fn () => ['role' => 'manager', 'status' => 'active']);
    }

    public function definition(): array
    {
        return ['community_id' => Community::factory(), 'user_id' => User::factory(), 'status' => 'active'];
    }
}
