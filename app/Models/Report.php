<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/** @property Carbon|null $incident_date */
class Report extends Model
{
    protected $table = 'reports';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['anonymous' => 'boolean', 'incident_date' => 'date', 'verified_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /** @return HasMany<ReportEvidence, $this> */
    public function evidence(): HasMany
    {
        return $this->hasMany(ReportEvidence::class)->orderBy('position');
    }

    /** @return BelongsToMany<User, $this> */
    public function seen(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'report_seen')->withPivot('seen_at');
    }
}
