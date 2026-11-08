<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CommunityPostPolicy
{
    public function view(?User $user, CommunityPost $post): Response
    {
        if ($post->status !== 'published' || ! $post->published_at || $post->published_at->isFuture()) {
            return Response::denyAsNotFound();
        }
        if ($post->community_id) {
            if (! Community::discoverable()->whereKey($post->community_id)->exists()) {
                return Response::denyAsNotFound();
            }
            $allowed = $post->audience === 'public' || ($post->audience === 'members' && $user && CommunityMembership::where('community_id', $post->community_id)->where('user_id', $user->id)->where('status', 'active')->exists());
        } else {
            $allowed = $user?->id === $post->author_id || ($post->audience === 'public' && $post->author?->public_profile);
        }

        return $allowed ? Response::allow() : Response::denyAsNotFound();
    }
}
