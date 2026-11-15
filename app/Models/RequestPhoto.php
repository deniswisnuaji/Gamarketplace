<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestPhoto extends Model
{
    protected $table = 'request_photos';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [];
    }
}
