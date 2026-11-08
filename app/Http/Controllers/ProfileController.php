<?php

namespace App\Http\Controllers;

use App\Models\CommunityMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __invoke(Request $request): View
    {
        $memberships = CommunityMembership::query()->where('user_id', $request->user()->id)
            ->whereHas('community', fn (Builder $query) => $query->discoverable())
            ->with('community')->latest()->paginate(12);

        return view('profile', compact('memberships'));
    }
}
