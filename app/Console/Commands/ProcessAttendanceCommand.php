<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAttendanceJob;
use Illuminate\Console\Command;

class ProcessAttendanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process attendance and send notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // استدعاء الخدمة والحصول على الإحصائيات
            $stats = app(\App\Services\AttendanceService::class)->processAttendance();

            // تمرير الإحصائية إلى Job
            ProcessAttendanceJob::dispatch($stats);

            $this->info('Attendance processing dispatched successfully.');
        } catch (\Exception $e) {
            $this->error('Error processing attendance: '.$e->getMessage());
        }
    }
}
