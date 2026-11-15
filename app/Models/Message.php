<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Message extends Model
{
    protected $table = 'messages';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [];
    }

    /** @return BelongsTo<Conversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /** @return BelongsTo<User, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function reads(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'message_reads')->withPivot('read_at');
    }

    /** @return HasOne<MeetupProposal, $this> */
    public function meetup(): HasOne
    {
        return $this->hasOne(MeetupProposal::class);
    }
}
