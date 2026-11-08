<?php

namespace App\Http\Controllers;

use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocialProfileController extends Controller
{
    public function edit(): View
    {
        return view('profiles.edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'bio' => ['nullable', 'string', 'max:1000'], 'city' => ['nullable', 'string', 'max:100'], 'public_profile' => ['required', 'boolean']]);
        $request->user()->update($data);

        return to_route('profile')->with('status', 'Perfil atualizado.');
    }

    public function show(Request $request, User $user): View
    {
        abort_unless($user->public_profile || $request->user()?->id === $user->id, 404);
        $posts = CommunityPost::where('author_id', $user->id)->whereNull('community_id')->when($request->user()?->id !== $user->id, fn ($query) => $query->publiclyVisible())->with('author')->latest('published_at')->withInteractions()->paginate(12);

        return view('profiles.show', compact('user', 'posts'));
    }
}
