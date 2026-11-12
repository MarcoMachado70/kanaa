<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserFollow;
use App\Support\DemoSeederSettings;
use Illuminate\Database\Seeder;

class FollowDemoSeeder extends Seeder
{
    public function run(): void
    {
        DemoSeederSettings::ensureAllowed();
        $users = User::where('email', 'like', '%@kanaa.test')->where('public_profile', true)->orderBy('id')->get();
        if ($users->count() < 2) {
            return;
        }
        foreach ($users as $index => $user) {
            foreach (range(1, min(4, $users->count() - 1)) as $offset) {
                $target = $users[($index + $offset) % $users->count()];
                UserFollow::firstOrCreate(['follower_id' => $user->id, 'followed_id' => $target->id]);
            }
        }
    }
}
