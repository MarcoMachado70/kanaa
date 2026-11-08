<?php

namespace App\Http\Controllers;

use App\Models\CommunityPost;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostPhotoController extends Controller
{
    public function __invoke(CommunityPost $post): StreamedResponse
    {
        Gate::authorize('view', $post);
        abort_unless($post->photo_path && Storage::disk('local')->exists($post->photo_path), 404);

        return Storage::disk('local')->response($post->photo_path, null, ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'private, no-store, max-age=0', 'X-Content-Type-Options' => 'nosniff'], 'inline');
    }
}
