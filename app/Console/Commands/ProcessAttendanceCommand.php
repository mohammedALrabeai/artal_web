<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessAttendanceJob;

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
        // استدعاء الـJob
        ProcessAttendanceJob::dispatch();
        $this->info('Attendance processing job dispatched successfully.');
    } catch (\Exception $e) {
        $this->error('Error processing attendance: ' . $e->getMessage());
    }
    }
}
