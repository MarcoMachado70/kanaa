<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\User;

class CommunityPolicy
{
    public function manage(User $user, Community $community): bool
    {
        return Community::query()->discoverable()->whereKey($community->id)->exists()
            && CommunityMembership::where('community_id', $community->id)->where('user_id', $user->id)
                ->where('role', 'manager')->where('status', 'active')->exists();
    }
}
