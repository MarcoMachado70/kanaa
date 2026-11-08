<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChurchLocation extends Model
{
    use HasFactory;

    protected $fillable = ['church_id', 'name', 'city', 'state', 'neighborhood', 'address', 'regular_schedule', 'accessibility', 'visitor_information', 'welcomes_visitors'];

    protected function casts(): array
    {
        return ['welcomes_visitors' => 'boolean'];
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
