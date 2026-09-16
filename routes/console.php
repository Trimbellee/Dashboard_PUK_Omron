<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule trash:clean
Schedule::command('trash:clean')
    ->daily();

// Schedule reminders:send - PRODUCTION
Schedule::command('reminders:send')
    ->dailyAt('08:00')
    ->name('reminders-send-production')
    ->appendOutputTo(storage_path('logs/reminder-schedule.log'));

Schedule::command('news:publish-scheduled')
    ->everyMinute()
    ->name('news-publish-scheduled')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/news-publish-scheduled.log'));