<?php

namespace App\Http\Controllers;

use App\Models\CommunityPost;
use App\Models\PostLike;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PostInteractionController extends Controller
{
    public function show(CommunityPost $post): View
    {
        Gate::authorize('view', $post);
        $post = CommunityPost::withInteractions()->with(['author', 'community'])->findOrFail($post->id);
        $comments = $post->comments()->with('user:id,name')->oldest()->orderBy('id')->paginate(20);

        return view('posts.show', compact('post', 'comments'));
    }

    public function like(Request $request, CommunityPost $post): RedirectResponse
    {
        Gate::authorize('view', $post);
        PostLike::firstOrCreate(['community_post_id' => $post->id, 'user_id' => $request->user()->id]);

        return to_route('posts.show', $post);
    }

    public function unlike(Request $request, CommunityPost $post): RedirectResponse
    {
        Gate::authorize('view', $post);
        $post->likes()->where('user_id', $request->user()->id)->delete();

        return to_route('posts.show', $post);
    }

    public function comment(Request $request, CommunityPost $post): RedirectResponse
    {
        Gate::authorize('view', $post);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']], ['body.required' => 'Escreva um comentário.', 'body.max' => 'Use até 2000 caracteres no comentário.']);
        $post->comments()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);

        return to_route('posts.show', $post)->with('status', 'Comentário publicado.');
    }

    public function destroyComment(Request $request, CommunityPost $post, int $comment): RedirectResponse
    {
        Gate::authorize('view', $post);
        $entry = $post->comments()->findOrFail($comment);
        abort_unless($entry->user_id === $request->user()->id, 403);
        $entry->delete();

        return to_route('posts.show', $post)->with('status','Comentário excluído.');
    }
}
