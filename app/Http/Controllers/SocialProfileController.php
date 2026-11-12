<?php

namespace App\Http\Controllers;

use App\Models\CommunityPost;
use App\Models\User;
use App\Models\UserFollow;
use Illuminate\Http\JsonResponse;
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

    public function show(Request $request, User $user): View|JsonResponse
    {
        abort_unless($user->public_profile || $request->user()?->id === $user->id, 404);
        $posts = CommunityPost::where('author_id', $user->id)->whereNull('community_id')->when($request->user()?->id !== $user->id, fn ($query) => $query->publiclyVisible())->with('author')->whereNotNull('published_at')->latest('published_at')->orderByDesc('id')->withInteractions()->cursorPaginate(12);

        if ($request->header('X-Kanaa-Feed') === 'posts') {
            return response()->json([
                'html' => view('components.post-feed-items', compact('posts'))->render(),
                'next' => $posts->nextPageUrl(),
            ])->header('Cache-Control', 'private, no-store');
        }

        $isFollowing = $request->user() && UserFollow::where('follower_id', $request->user()->id)->where('followed_id', $user->id)->exists();

        return view('profiles.show', compact('user', 'posts', 'isFollowing'));
    }
}
