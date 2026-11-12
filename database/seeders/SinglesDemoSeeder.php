<?php

namespace Database\Seeders;

use App\Models\SingleInterest;
use App\Models\SingleMatch;
use App\Models\SingleProfile;
use App\Models\User;
use App\Support\DemoSeederSettings;
use App\Support\SinglesAccess;
use Illuminate\Database\Seeder;

class SinglesDemoSeeder extends Seeder
{
    public function run(): void
    {
        DemoSeederSettings::ensureAllowed();
        $users = User::where('email', 'like', '%@kanaa.test')->orderBy('id')->limit(12)->get();
        foreach ($users as $index => $user) {
            SingleProfile::firstOrCreate(['user_id' => $user->id], [
                'birth_date' => (1990 + $index).'-01-15',
                'about' => 'Perfil fictício de demonstração. Valorizo a fé, a família e encontros com propósito.',
                'city' => $index % 2 === 0 ? 'Curitiba' : 'São Paulo',
                'affinities' => $index % 2 === 0 ? ['fé', 'voluntariado', 'família'] : ['fé', 'música', 'natureza'],
                'active' => $index !== 11, 'consented_at' => now(),
            ]);
        }
        for ($index = 0; $index + 1 < min(6, $users->count()); $index += 2) {
            $a = $users[$index]->id;
            $b = $users[$index + 1]->id;
            if (SingleProfile::eligible()->whereIn('user_id', [$a, $b])->count() !== 2 || app(SinglesAccess::class)->blocked($a, $b)) {
                continue;
            }
            $match = SingleMatch::firstOrCreate(['low_user_id' => min($a, $b), 'high_user_id' => max($a, $b)], ['status' => 'active']);
            if ($match->status === 'active') {
                SingleInterest::firstOrCreate(['from_user_id' => $a, 'to_user_id' => $b]);
                SingleInterest::firstOrCreate(['from_user_id' => $b, 'to_user_id' => $a]);
            }
        }
    }
}
