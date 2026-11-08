<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\CommunityPost;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'posts' => CommunityPost::query()->publiclyVisible()->with(['community', 'author'])->orderByDesc('published_at')->orderByDesc('id')->withInteractions()->paginate(8),
            'communities' => Community::query()->discoverable()->orderBy('name')->limit(4)->get(),
        ]);
    }
}
