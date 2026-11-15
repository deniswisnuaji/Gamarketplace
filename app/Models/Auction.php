<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property CarbonInterface|null $ends_at
 * @property CarbonInterface|null $finalized_at */
class Auction extends Model
{
    protected $table = 'auctions';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['ends_at' => 'datetime', 'finalized_at' => 'datetime'];
    }

    /** @return BelongsTo<Listing, $this> */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /** @return HasMany<Bid, $this> */
    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class)->orderBy('id');
    }

    /** @return BelongsTo<Bid, $this> */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(Bid::class, 'winner_bid_id');
    }
}
