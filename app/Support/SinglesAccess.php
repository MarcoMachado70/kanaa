<?php

namespace App\Support;

use App\Models\SingleBlock;
use App\Models\SingleContact;
use App\Models\SingleInterest;
use App\Models\SingleMatch;
use App\Models\SingleProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SinglesAccess
{
    public function participant(int $id): SingleProfile
    {
        return SingleProfile::eligible()->where('user_id', $id)->firstOrFail();
    }

    public function blocked(int $a, int $b): bool
    {
        return SingleBlock::where(fn (Builder $q) => $q->where('from_user_id', $a)->where('to_user_id', $b))->orWhere(fn (Builder $q) => $q->where('from_user_id', $b)->where('to_user_id', $a))->exists();
    }

    public function pair(int $a, int $b): void
    {
        abort_if($a === $b, 422);
        $this->participant($a);
        $this->participant($b);
        abort_if($this->blocked($a, $b), 404);
    }

    public function lock(int $a, int $b): void
    {
        User::whereIn('id', [$a, $b])->orderBy('id')->lockForUpdate()->get();
    }

    public function match(int $viewer, SingleMatch $match): int
    {
        abort_unless($match->status === 'active' && in_array($viewer, [$match->low_user_id, $match->high_user_id], true), 404);
        $other = $viewer === $match->low_user_id ? $match->high_user_id : $match->low_user_id;
        $this->pair($viewer, $other);

        return $other;
    }

    public function end(int $a, ?int $b = null): void
    {
        $query = SingleMatch::where(fn (Builder $q) => $q->where('low_user_id', $a)->orWhere('high_user_id', $a));
        if ($b !== null) {
            $query->where(fn (Builder $q) => $q->where('low_user_id', $b)->orWhere('high_user_id', $b));
        }
        SingleContact::whereIn('single_match_id', (clone $query)->select('id'))->delete();
        $query->update(['status' => 'ended']);
        $interests = SingleInterest::where(fn (Builder $q) => $q->where('from_user_id', $a)->orWhere('to_user_id', $a));
        if ($b !== null) {
            $interests->where(fn (Builder $q) => $q->where('from_user_id', $b)->orWhere('to_user_id', $b));
        }
        $interests->delete();
    }

    public function candidates(int $viewer): Builder
    {
        return SingleProfile::eligible()->where('user_id', '!=', $viewer)->whereNotIn('user_id', SingleBlock::where('from_user_id', $viewer)->select('to_user_id'))->whereNotIn('user_id',SingleBlock::where('to_user_id',$viewer)->select('from_user_id'));
    }
}
