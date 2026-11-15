<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bid extends Model
{
    protected $table = 'bids';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_buy_now' => 'boolean'];
    }

    /** @return BelongsTo<Auction, $this> */
    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    /** @return BelongsTo<User, $this> */
    public function bidder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bidder_id');
    }
}
