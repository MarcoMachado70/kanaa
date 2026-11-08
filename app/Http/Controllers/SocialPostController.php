<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\CommunityPost;
use App\Support\PostPhotoStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SocialPostController extends Controller
{
    public function store(Request $request, PostPhotoStorage $photos): RedirectResponse
    {
        $data = $request->validate(['body' => ['required_without:photo', 'nullable', 'string', 'max:5000'], 'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=3000,max_height=3000'], 'title' => ['nullable', 'string', 'max:150'], 'audience' => ['required', Rule::in(['public', 'private', 'members'])], 'community_id' => ['nullable', 'integer']]);
        $community = null;
        if (! empty($data['community_id'])) {
            $community = Community::discoverable()->findOrFail($data['community_id']);
            abort_unless(CommunityMembership::where('community_id', $community->id)->where('user_id', $request->user()->id)->where('status', 'active')->exists(), 403);
        } else {
            abort_if($data['audience'] === 'members', 422);
        }
        $path = $request->hasFile('photo') ? $photos->store($request->file('photo')->getRealPath()) : null;
        try {
            $post = new CommunityPost(['author_id' => $request->user()->id, 'community_id' => $community?->id, 'title' => $data['title'] ?? '', 'body' => $data['body'] ?? '', 'audience' => $data['audience'], 'status' => 'published', 'published_at' => now()]);
            $post->photo_path = $path;
            $post->save();
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return $community ? to_route('communities.show', $community->slug)->with('status', 'Publicação criada.') : to_route('people.show', $request->user())->with('status', 'Publicação criada.');
    }

    public function destroy(Request $request, CommunityPost $post): RedirectResponse
    {
        abort_unless($post->author_id === $request->user()->id, 403);
        $post->delete();

        return to_route('profile')->with('status', 'Publicação excluída.');
    }
}
