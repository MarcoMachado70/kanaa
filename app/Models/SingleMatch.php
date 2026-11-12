<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SingleMatch extends Model
{
    use HasFactory;

    protected $fillable = ['low_user_id', 'high_user_id', 'status'];

    public function lowUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'low_user_id');
    }

    public function highUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'high_user_id');
    }
}
