<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\CommunityMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CommunityController extends Controller
{
    public function create(): View
    {
        return view('communities.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'description' => ['required', 'string', 'max:2000'], 'city' => ['required', 'string', 'max:100'], 'state' => ['required', 'regex:/^[A-Za-z]{2}$/'], 'type' => ['required', Rule::in(array_keys(Community::TYPES))], 'visibility' => ['required', Rule::in(['public', 'approval'])]]);
        $community = DB::transaction(function () use ($data, $request) {
            $community = Community::create([...$data, 'state' => strtoupper($data['state']), 'slug' => Str::slug($data['name']).'-'.Str::uuid(), 'status' => 'published']);
            $membership = CommunityMembership::create(['community_id' => $community->id, 'user_id' => $request->user()->id, 'status' => 'active']);
            $membership->role = 'manager';
            $membership->save();

            return $community;
        });

        return to_route('communities.show', $community->slug)->with('status', 'Comunidade criada! Você é o gestor deste espaço.');
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(array_keys(Community::TYPES))],
        ]);

        $communities = Community::query()->discoverable()->with('church')
            ->when($filters['q'] ?? null, fn (Builder $query, string $term) => $query->whereLike('name', '%'.trim($term).'%'))
            ->when($filters['city'] ?? null, fn (Builder $query, string $city) => $query->whereLike('city', trim($city)))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->orderBy('name')->orderBy('id')->paginate(9)->withQueryString();

        return view('communities.index', compact('communities', 'filters'));
    }

    public function show(Request $request, string $slug): View|JsonResponse
    {
        $community = Community::query()->discoverable()->with('church')->where('slug', $slug)->firstOrFail();
        $active = $request->user() && CommunityMembership::where('community_id', $community->id)->where('user_id', $request->user()->id)->where('status', 'active')->exists();
        $posts = $community->posts()->where('status', 'published')->where('published_at', '<=', now())->whereIn('audience', $active ? ['public', 'members'] : ['public'])->with(['author', 'community'])->latest('published_at')->orderByDesc('id')->withInteractions()->cursorPaginate(8);

        $membership = $request->user() ? CommunityMembership::where('community_id', $community->id)->where('user_id', $request->user()->id)->first() : null;

        if ($request->header('X-Kanaa-Feed') === 'posts') {
            return response()->json([
                'html' => view('components.post-feed-items', compact('posts'))->render(),
                'next' => $posts->nextPageUrl(),
            ])->header('Cache-Control', 'private, no-store');
        }

        return view('communities.show', compact('community', 'posts', 'membership'));
    }
}
