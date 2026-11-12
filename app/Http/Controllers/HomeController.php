<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\CommunityPost;
use App\Models\UserFollow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View|JsonResponse|RedirectResponse
    {
        $request->validate(['feed' => ['nullable', Rule::in(['all', 'following'])]]);
        $following = $request->query('feed') === 'following';
        if ($following && ! $request->user()) {
            return redirect()->guest(route('login'));
        }
        $posts = CommunityPost::query()->publiclyVisible()->when($following, fn ($query) => $query->whereIn('author_id', UserFollow::select('followed_id')->where('follower_id', $request->user()->id)))->with(['community', 'author'])->orderByDesc('published_at')->orderByDesc('id')->withInteractions()->cursorPaginate(8)->withQueryString();

        if ($request->header('X-Kanaa-Feed') === 'posts') {
            return response()->json([
                'html' => view('components.post-feed-items', compact('posts'))->render(),
                'next' => $posts->nextPageUrl(),
            ])->header('Cache-Control', 'private, no-store');
        }

        return view('home', [
            'posts' => $posts,
            'communities' => Community::query()->discoverable()->orderBy('name')->limit(4)->get(),
        ]);
    }
}
