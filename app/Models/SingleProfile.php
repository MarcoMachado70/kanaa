<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SingleProfile extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'birth_date', 'about', 'city', 'affinities', 'active', 'consented_at'];

    protected $hidden = ['birth_date', 'consented_at'];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'affinities' => 'array', 'active' => 'boolean', 'consented_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[Scope]
    protected function eligible(Builder $query): void
    {
        $query->where('active', true)->whereNotNull('consented_at')->whereDate('birth_date', '<=', now()->subYears(18)->toDateString());
    }

    public const AFFINITIES = ['fé' => 'Fé', 'família' => 'Família', 'voluntariado' => 'Voluntariado', 'música' => 'Música', 'estudos' => 'Estudos bíblicos', 'natureza' => 'Natureza'];
}
