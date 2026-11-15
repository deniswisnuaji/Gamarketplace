<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceNotification extends Model
{
    protected $table = 'notifications';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
