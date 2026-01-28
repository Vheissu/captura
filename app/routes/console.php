<?php

use App\Jobs\CleanupOldScreenshots;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

app()->booted(function () {
    app(Schedule::class)
        ->job(new CleanupOldScreenshots())
        ->hourly();
});
