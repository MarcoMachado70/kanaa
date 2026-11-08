<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class CommunityPost extends Model
{
    use HasFactory;

    protected $hidden = ['photo_path'];

    protected static function booted(): void
    {
        static::deleted(function (self $post): void {
            if ($post->photo_path) {
                Storage::disk('local')->delete($post->photo_path);
            }
        });
    }

    protected $fillable = ['author_id', 'community_id', 'title', 'body', 'audience', 'status', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    #[Scope]
    protected function publiclyVisible(Builder $query): void
    {
        $query->where('status', 'published')->where('audience', 'public')
            ->where('published_at', '<=', now())->where(fn (Builder $visible) => $visible->whereHas('community', fn (Builder $community) => $community->discoverable())->orWhere(fn (Builder $personal) => $personal->whereNull('community_id')->whereHas('author', fn (Builder $author) => $author->where('public_profile', true))));
    }

    #[Scope]
    protected function withInteractions(Builder $query): void
    {
        $query->withCount(['likes', 'comments'])->withExists(['likes as liked_by_me' => fn (Builder $likes) => $likes->where('user_id', auth()->id() ?? 0)]);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }
}
