<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Listing extends Model
{
    protected $table = 'listings';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'negotiable' => 'boolean', 'published_price_rupiah' => 'integer'];
    }

    use SoftDeletes;

    /** @return BelongsTo<User, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<MeetupPoint, $this> */
    public function point(): BelongsTo
    {
        return $this->belongsTo(MeetupPoint::class, 'meetup_point_id');
    }

    /** @return HasMany<ListingPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(ListingPhoto::class)->orderBy('position');
    }

    /** @return HasOne<Auction, $this> */
    public function auction(): HasOne
    {
        return $this->hasOne(Auction::class);
    }

    /** @param Builder<Listing> $query
     * @return Builder<Listing> */
    public function scopeVisibleTo(Builder $query, ?User $viewer): Builder
    {
        if ($viewer?->role === 'admin') {
            return $query;
        }

        return $query->where(function ($q) use ($viewer) {
            $q->where(fn ($public) => $public->where('moderation', 'approved')->whereHas('seller', fn ($u) => $u->where('status', 'active')));
            if ($viewer && $viewer->status === 'active') {
                $q->orWhere('seller_id', $viewer->id);
            }
        });
    }
}
