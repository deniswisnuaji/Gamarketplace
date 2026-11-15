<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetupPoint extends Model
{
    protected $table = 'meetup_points';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [];
    }
}
