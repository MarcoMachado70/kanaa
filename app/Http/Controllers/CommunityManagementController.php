<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\CommunityMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CommunityManagementController extends Controller
{
    public function index(Community $community): View
    {
        Gate::authorize('manage', $community);
        $pending = CommunityMembership::where('community_id', $community->id)->where('status', 'pending')
            ->with('user:id,name')->oldest()->orderBy('id')->paginate(15);
        $activeCount = CommunityMembership::where('community_id', $community->id)->where('status', 'active')->count();

        return view('communities.manage', compact('community', 'pending', 'activeCount'));
    }

    public function update(Request $request, Community $community, int $membership): RedirectResponse
    {
        Gate::authorize('manage', $community);
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'reject'])]]);
        $entry = CommunityMembership::where('community_id', $community->id)->findOrFail($membership);
        $updated = CommunityMembership::whereKey($entry->id)->where('status', 'pending')->where('role', 'member')->update([
            'status' => $data['decision'] === 'approve' ? 'active' : 'rejected',
            'reviewed_by' => $request->user()->id, 'reviewed_at' => now(),
        ]);
        abort_unless($updated === 1, 409, 'Esta solicitação já foi analisada ou cancelada.');

        return to_route('communities.manage', $community)->with('status', $data['decision'] === 'approve' ? 'Participação aprovada. Boas-vindas à comunidade!' : 'Solicitação recusada.');
    }
}
