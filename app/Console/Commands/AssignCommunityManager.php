<?php

namespace App\Console\Commands;

use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\User;
use Illuminate\Console\Command;

class AssignCommunityManager extends Command
{
    protected $signature = 'kanaa:assign-manager {community : Slug da comunidade} {email : E-mail da conta existente}';

    protected $description = 'Atribui a gestão de uma comunidade a uma conta existente';

    public function handle(): int
    {
        $community = Community::query()->discoverable()->where('slug', $this->argument('community'))->first();
        $user = User::where('email', mb_strtolower(trim($this->argument('email'))))->first();
        if (! $community || ! $user) {
            $this->error('Comunidade publicada ou conta não encontrada.');

            return self::FAILURE;
        }
        $membership = CommunityMembership::firstOrNew(['community_id' => $community->id, 'user_id' => $user->id]);
        $membership->role = 'manager';
        $membership->status = 'active';
        $membership->save();
        $this->info('Gestão atribuída. O painel está disponível na página da comunidade.');

        return self::SUCCESS;
    }
}
