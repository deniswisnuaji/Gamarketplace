<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/** @property Carbon $needed_by */
class WantedRequest extends Model
{
    protected $table = 'wanted_requests';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['needed_by' => 'datetime', 'found' => 'boolean', 'removed' => 'boolean'];
    }

    use SoftDeletes;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    /** @return HasMany<RequestPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(RequestPhoto::class, 'request_id')->orderBy('position');
    }

    /** @return HasMany<RequestOffer, $this> */
    public function offers(): HasMany
    {
        return $this->hasMany(RequestOffer::class, 'request_id');
    }
}
