<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SingleContact extends Model
{
    use HasFactory;

    protected $fillable = ['single_match_id', 'user_id', 'channel', 'value', 'consented_at'];

    protected $hidden = ['value'];

    protected function casts(): array
    {
        return ['value' => 'encrypted', 'consented_at' => 'datetime'];
    }
}
