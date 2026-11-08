<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Community extends Model
{
    use HasFactory;

    public const TYPES = ['jovens' => 'Jovens', 'estudos' => 'Estudos bíblicos', 'voluntariado' => 'Voluntariado', 'oracao' => 'Oração', 'musica' => 'Música'];

    protected $fillable = ['church_id', 'name', 'slug', 'description', 'type', 'city', 'state', 'visibility', 'status'];

    #[Scope]
    protected function discoverable(Builder $query): void
    {
        $query->where('status', 'published')->whereIn('visibility', ['public', 'approval'])
            ->where(fn (Builder $query) => $query->whereNull('church_id')->orWhereHas('church', fn (Builder $church) => $church->where('status', 'published')));
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(CommunityPost::class);
    }
}
