<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Define scheduled tasks here using the Schedule facade.
// Example: Schedule::command('queue:work')->everyMinute()->withoutOverlapping();
// Schedule::command('sanctum:prune-expired --hours=24')->daily();
