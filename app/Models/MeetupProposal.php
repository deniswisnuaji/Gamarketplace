<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetupProposal extends Model
{
    protected $table = 'meetup_proposals';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['responded_at' => 'datetime'];
    }

    /** @return BelongsTo<MeetupPoint, $this> */
    public function point(): BelongsTo
    {
        return $this->belongsTo(MeetupPoint::class, 'meetup_point_id');
    }

    /** @return BelongsTo<Message, $this> */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
