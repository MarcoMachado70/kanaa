<?php

namespace App\Http\Controllers;

use App\Models\Church;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChurchController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'size:2'],
            'denomination' => ['nullable', 'string', 'max:100'],
            'visitors' => ['nullable', 'boolean'],
        ], ['city.max' => 'Informe uma cidade com até 100 caracteres.']);

        $locations = function (Builder|Relation $query) use ($filters): void {
            if (! empty($filters['city'])) {
                $query->whereLike('city', trim($filters['city']));
            }
            if (! empty($filters['state'])) {
                $query->where('state', strtoupper($filters['state']));
            }
            if (! empty($filters['visitors'])) {
                $query->where('welcomes_visitors', true);
            }
        };

        $churches = Church::query()->where('status', 'published')
            ->withWhereHas('locations', $locations)
            ->when($filters['denomination'] ?? null, fn (Builder $query, string $denomination) => $query->where('denomination', $denomination))
            ->orderBy('name')->orderBy('id')->paginate(9)->withQueryString();

        return view('churches.index', [
            'churches' => $churches,
            'filters' => $filters,
            'denominations' => Church::query()->where('status', 'published')->whereNotNull('denomination')->distinct()->orderBy('denomination')->pluck('denomination'),
        ]);
    }

    public function show(Church $church): View
    {
        abort_unless($church->status === 'published', 404);

        return view('churches.show', ['church' => $church->load('locations')]);
    }
}
