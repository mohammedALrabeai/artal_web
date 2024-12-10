<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use Carbon\Carbon;

class MigrateAttendanceData extends Command
{
    protected $signature = 'attendance:migrate-data';
    protected $description = 'Migrate old check-in and check-out data to new datetime columns';

    public function handle()
    {
        $this->info('Starting migration of attendance data...');

        Attendance::chunk(100, function ($attendances) {
            foreach ($attendances as $attendance) {
                // دمج التاريخ مع وقت الحضور
                if ($attendance->date && $attendance->check_in) {
                    $attendance->check_in_datetime = Carbon::createFromFormat(
                        'Y-m-d H:i:s', 
                        $attendance->date . ' ' . $attendance->check_in
                    );
                }

                // دمج التاريخ مع وقت الانصراف
                if ($attendance->date && $attendance->check_out) {
                    $attendance->check_out_datetime = Carbon::createFromFormat(
                        'Y-m-d H:i:s', 
                        $attendance->date . ' ' . $attendance->check_out
                    );
                }

                $attendance->save();
            }
        });

        $this->info('Migration completed successfully!');
    }
}
