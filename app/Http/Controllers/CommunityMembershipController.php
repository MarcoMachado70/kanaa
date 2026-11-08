<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\CommunityMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommunityMembershipController extends Controller
{
    public function store(Request $request, string $slug): RedirectResponse
    {
        $community = Community::query()->discoverable()->where('slug', $slug)->firstOrFail();
        CommunityMembership::firstOrCreate([
            'community_id' => $community->id, 'user_id' => $request->user()->id,
        ], ['status' => $community->visibility === 'approval' ? 'pending' : 'active']);

        return to_route('communities.show', $slug)->with('status', 'Sua participação foi registrada.');
    }

    public function destroy(Request $request, string $slug): RedirectResponse
    {
        $community = Community::where('slug', $slug)->firstOrFail();
        CommunityMembership::where('community_id', $community->id)->where('user_id', $request->user()->id)->delete();

        return to_route('profile')->with('status', 'Sua participação foi encerrada.');
    }
}
