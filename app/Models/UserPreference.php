<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $table = 'user_preferences';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [];
    }

    protected $primaryKey = 'user_id';

    public $incrementing = false;
}
