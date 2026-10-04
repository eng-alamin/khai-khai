<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Needs ONE server cron line:  * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('orders:process-timeouts')
    ->everyMinute()
    ->withoutOverlapping(5);

// Applies each vendor's weekly opening hours (only for vendors who switched it on).
Schedule::command('restaurants:sync-opening-hours')
    ->everyMinute()
    ->withoutOverlapping(5);
