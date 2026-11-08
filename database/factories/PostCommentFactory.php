<?php

namespace Database\Factories;

use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostCommentFactory extends Factory
{
    public function definition(): array
    {
        return ['community_post_id' => CommunityPost::factory(), 'user_id' => User::factory(), 'body' => fake()->sentence()];
    }
}
