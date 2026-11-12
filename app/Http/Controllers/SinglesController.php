<?php

namespace App\Http\Controllers;

use App\Models\SingleBlock;
use App\Models\SingleContact;
use App\Models\SingleInterest;
use App\Models\SingleMatch;
use App\Models\SingleProfile;
use App\Models\SingleReport;
use App\Models\User;
use App\Support\SinglesAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SinglesController extends Controller
{
    public function __construct(private SinglesAccess $access) {}

    public function index(Request $request): Response
    {
        $mine = SingleProfile::where('user_id', $request->user()->id)->first();
        $eligible = SingleProfile::eligible()->where('user_id', $request->user()->id)->exists();
        $filters = $request->validate(['city' => ['nullable', 'string', 'max:100'], 'affinity' => ['nullable', Rule::in(array_keys(SingleProfile::AFFINITIES))]]);
        $profiles = $eligible ? $this->access->candidates($request->user()->id)->with('user:id,name')->when($filters['city'] ?? null, fn (Builder $q, string $city) => $q->whereLike('city', trim($city)))->when($filters['affinity'] ?? null, fn (Builder $q, string $affinity) => $q->whereJsonContains('affinities', $affinity))->orderBy('id')->paginate(12)->withQueryString() : null;
        $sent = $eligible ? SingleInterest::where('from_user_id', $request->user()->id)->pluck('to_user_id')->all() : [];
        $matches = $eligible ? SingleMatch::where('status', 'active')->where(fn (Builder $q) => $q->where('low_user_id', $request->user()->id)->orWhere('high_user_id', $request->user()->id))->whereHas('lowUser.singleProfile', fn (Builder $q) => $q->eligible())->whereHas('highUser.singleProfile', fn (Builder $q) => $q->eligible())->with(['lowUser:id,name', 'highUser:id,name'])->latest()->paginate(8, ['*'], 'matches_page') : null;

        return response()->view('singles.index', compact('mine', 'eligible', 'filters', 'profiles', 'sent', 'matches'))->header('Cache-Control', 'private, no-store');
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now()->subYears(18)->toDateString(), 'after:1900-01-01'],
            'about' => ['required', 'string', 'max:1000'], 'city' => ['required', 'string', 'max:100'],
            'affinities' => ['required', 'array', 'min:1', 'max:6'], 'affinities.*' => ['required', 'distinct', Rule::in(array_keys(SingleProfile::AFFINITIES))],
            'consent' => ['accepted'],
        ], ['birth_date.before_or_equal' => 'A área de solteiros é exclusiva para maiores de 18 anos.', 'consent.accepted' => 'Confirme sua adesão voluntária para continuar.']);
        DB::transaction(function () use ($request, $data): void {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            SingleProfile::updateOrCreate(['user_id' => $request->user()->id], [
                'birth_date' => $data['birth_date'], 'about' => $data['about'], 'city' => $data['city'],
                'affinities' => $data['affinities'], 'active' => true, 'consented_at' => now(),
            ]);
        });

        return to_route('singles.index')->with('status', 'Seu perfil de solteiros está ativo.');
    }

    public function pause(Request $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            SingleProfile::where('user_id', $request->user()->id)->update(['active' => false]);
            $this->access->end($request->user()->id);
        });

        return to_route('singles.index')->with('status', 'Perfil pausado. Seus matches e compartilhamentos foram encerrados.');
    }

    public function interest(Request $request, SingleProfile $profile): RedirectResponse
    {
        $matched = DB::transaction(function () use ($request, $profile): bool {
            $a = $request->user()->id;
            $b = $profile->user_id;
            $this->access->lock($a, $b);
            $this->access->pair($a, $b);
            SingleInterest::firstOrCreate(['from_user_id' => $a, 'to_user_id' => $b]);
            if (! SingleInterest::where('from_user_id', $b)->where('to_user_id', $a)->exists()) {
                return false;
            }
            SingleMatch::updateOrCreate(['low_user_id' => min($a, $b), 'high_user_id' => max($a, $b)], ['status' => 'active']);

            return true;
        }, 3);

        return to_route('singles.index')->with('status', $matched ? 'Vocês têm interesse mútuo! Um novo match está disponível.' : 'Interesse registrado de forma privada.');
    }

    public function cancelInterest(Request $request, SingleProfile $profile): RedirectResponse
    {
        DB::transaction(function () use ($request, $profile): void {
            $this->access->lock($request->user()->id, $profile->user_id);
            $this->access->end($request->user()->id, $profile->user_id);
        });

        return to_route('singles.index')->with('status', 'Interesse retirado. Eventuais matches e autorizações foram encerrados.');
    }

    public function showMatch(Request $request, SingleMatch $match): Response
    {
        $other = $this->access->match($request->user()->id, $match);
        $person = SingleProfile::where('user_id', $other)->with('user:id,name')->firstOrFail();
        $authorized = SingleContact::where('single_match_id', $match->id)->where('user_id', $request->user()->id)->exists();
        $ready = SingleContact::where('single_match_id', $match->id)->count() === 2;

        return response()->view('singles.match', compact('match', 'person', 'authorized', 'ready'))->header('Cache-Control', 'private, no-store');
    }

    public function endMatch(Request $request, SingleMatch $match): RedirectResponse
    {
        DB::transaction(function () use ($request, $match): void {
            abort_unless(in_array($request->user()->id, [$match->low_user_id, $match->high_user_id], true), 404);
            $this->access->lock($match->low_user_id, $match->high_user_id);
            $this->access->end($match->low_user_id, $match->high_user_id);
        });

        return to_route('singles.index')->with('status', 'Match desfeito e autorizações revogadas.');
    }

    public function authorizeContact(Request $request, SingleMatch $match): RedirectResponse
    {
        $this->access->match($request->user()->id, $match);
        $data = $request->validate([
            'channel' => ['required', Rule::in(['email', 'whatsapp', 'instagram'])],
            'value' => ['required', 'string', 'max:150', Rule::when($request->input('channel') === 'email', ['email']), Rule::when($request->input('channel') === 'whatsapp', ['regex:/^\+?[0-9 ()-]{8,25}$/']), Rule::when($request->input('channel') === 'instagram', ['regex:/^@?[A-Za-z0-9_.]{1,30}$/'])],
            'consent' => ['accepted'],
        ]);
        DB::transaction(function () use ($request, $match, $data): void {
            $this->access->lock($match->low_user_id, $match->high_user_id);
            $this->access->match($request->user()->id, $match->fresh());
            SingleContact::updateOrCreate(['single_match_id' => $match->id, 'user_id' => $request->user()->id], ['channel' => $data['channel'], 'value' => $data['value'], 'consented_at' => now()]);
        });

        return to_route('singles.match', $match)->with('status', 'Autorização registrada. O contato só aparece após a autorização dos dois.');
    }

    public function revokeContact(Request $request, SingleMatch $match): RedirectResponse
    {
        abort_unless(in_array($request->user()->id, [$match->low_user_id, $match->high_user_id], true), 404);
        DB::transaction(function () use ($request, $match): void {
            $this->access->lock($match->low_user_id, $match->high_user_id);
            SingleContact::where('single_match_id', $match->id)->where('user_id', $request->user()->id)->delete();
        });

        return to_route('singles.index')->with('status', 'Sua autorização de contato foi revogada.');
    }

    public function contact(Request $request, SingleMatch $match): Response
    {
        $contact = DB::transaction(function () use ($request, $match): SingleContact {
            abort_unless(in_array($request->user()->id, [$match->low_user_id, $match->high_user_id], true), 404);
            $this->access->lock($match->low_user_id, $match->high_user_id);
            $other = $this->access->match($request->user()->id, $match->fresh());
            abort_unless(SingleContact::where('single_match_id', $match->id)->count() === 2, 409, 'A liberação exige autorização das duas pessoas.');

            return SingleContact::where('single_match_id', $match->id)->where('user_id', $other)->firstOrFail();
        });

        return response()->view('singles.contact', compact('match', 'contact'))->header('Cache-Control', 'private, no-store, max-age=0')->header('Referrer-Policy', 'no-referrer');
    }

    public function block(Request $request, SingleProfile $profile): RedirectResponse
    {
        $this->access->participant($request->user()->id);
        abort_if($request->user()->id === $profile->user_id, 422);
        DB::transaction(function () use ($request, $profile): void {
            $this->access->lock($request->user()->id, $profile->user_id);
            SingleBlock::firstOrCreate(['from_user_id' => $request->user()->id, 'to_user_id' => $profile->user_id]);
            $this->access->end($request->user()->id, $profile->user_id);
        });

        return to_route('singles.index')->with('status', 'Perfil bloqueado nesta área. Matches e contatos foram encerrados.');
    }

    public function report(Request $request, SingleProfile $profile): RedirectResponse
    {
        $this->access->participant($request->user()->id);
        abort_if($request->user()->id === $profile->user_id, 422);
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);
        SingleReport::create(['from_user_id' => $request->user()->id, 'to_user_id' => $profile->user_id, 'reason' => $data['reason'], 'status' => 'pending']);

        return to_route('singles.index')->with('status', 'Denúncia registrada para análise. Você também pode bloquear o perfil.');
    }
}
