<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModerationAction extends Model
{
    protected $table = 'moderation_actions';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [];
    }
}
