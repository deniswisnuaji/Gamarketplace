<?php

use App\Services\AuctionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('marketplace:finalize-auctions', function () {
    $count = app(AuctionService::class)->finalizeDue();
    $this->info("Finalized {$count} auctions.");
})->purpose('Close due auctions and notify winners');

Schedule::command('marketplace:finalize-auctions')->everyMinute()->withoutOverlapping();
