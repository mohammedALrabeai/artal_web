<?php

namespace App\Schedulers;

use Illuminate\Console\Scheduling\Schedule;
use App\Jobs\ProcessAttendanceJob;

class ScheduleTasks
{
    /**
     * Configure the application's scheduled tasks.
     *
     * @param Schedule $schedule
     * @return void
     */
    public static function configure(Schedule $schedule)
    {
        // جدولة تنفيذ Job كل ساعة everyMinute
        // $schedule->job(new ProcessAttendanceJob())->hourly();
        $schedule->job(new ProcessAttendanceJob())->everyMinute();
    }
}
