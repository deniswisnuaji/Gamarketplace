<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActivity extends Model
{
    protected $table = 'admin_activity';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [];
    }
}
