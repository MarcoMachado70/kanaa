<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserFollow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PeopleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'city' => ['nullable', 'string', 'max:100'], 'tab' => ['nullable', Rule::in(['all', 'following'])]]);
        $query = User::query()->where('public_profile', true)->where('id', '!=', $request->user()->id);
        if (($filters['tab'] ?? 'all') === 'following') {
            $query->whereIn('id', UserFollow::select('followed_id')->where('follower_id', $request->user()->id));
        }
        $people = $query->when($filters['q'] ?? null, fn (Builder $query, string $term) => $query->whereLike('name', '%'.trim($term).'%'))->when($filters['city'] ?? null, fn (Builder $query, string $city) => $query->whereLike('city', trim($city)))->orderBy('name')->orderBy('id')->paginate(12)->withQueryString();
        $following = UserFollow::where('follower_id', $request->user()->id)->whereIn('followed_id', $people->pluck('id'))->pluck('followed_id')->all();

        return view('people.index', compact('people', 'following', 'filters'));
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->public_profile, 404);
        abort_if($user->id === $request->user()->id, 422, 'Você não pode seguir o próprio perfil.');
        UserFollow::firstOrCreate(['follower_id' => $request->user()->id, 'followed_id' => $user->id]);

        return to_route('people.show', $user)->with('status', 'Agora você acompanha este perfil.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        UserFollow::where('follower_id', $request->user()->id)->where('followed_id', $user->id)->delete();

        return to_route('people.index', ['tab' => 'following'])->with('status', 'Você deixou de seguir este perfil.');
    }
}
