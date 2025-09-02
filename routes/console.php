<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();



Schedule::command('app:check-subscription-status')
    ->dailyAt('09:00')
    ->withoutOverlapping();

// Or run hourly during business hours
// Schedule::command('app:check-subscription-status')
//     ->hourly()
//     ->between('8:00', '18:00')
//     ->withoutOverlapping();
