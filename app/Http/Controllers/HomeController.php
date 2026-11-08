<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\CommunityPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View|JsonResponse
    {
        $posts = CommunityPost::query()->publiclyVisible()->with(['community', 'author'])->orderByDesc('published_at')->orderByDesc('id')->withInteractions()->cursorPaginate(8);

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
