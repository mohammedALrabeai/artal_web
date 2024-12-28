<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();



use Illuminate\Console\Scheduling\Schedule;
use App\Jobs\ProcessAttendanceJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// جدولة المهمة لتعمل كل ساعة
app(Schedule::class)->call(function () {
    dispatch(new ProcessAttendanceJob());
})->hourly();
