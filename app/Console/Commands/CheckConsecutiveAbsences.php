<?php

namespace App\Console\Commands;

use App\Exports\ConsecutiveAbsenceExport;
use App\Mail\ConsecutiveAbsenceReportMail;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\OtpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class CheckConsecutiveAbsences extends Command
{
    protected $signature = 'attendance:check-absences';

    protected $description = 'Check employees with consecutive absences and send Excel report';

    public function handle()
    {
        try {
            $targetDays = 3;
            $today = now()->startOfDay();
            $exportData = collect();

            Employee::where('status', true)
                ->whereHas('projectRecords', function ($q) use ($today) {
                    $q->where('status', 1)
                        ->where(function ($query) use ($today) {
                            $query->whereNull('end_date')
                                ->orWhere('end_date', '>=', $today);
                        })
                        ->where('start_date', '<=', $today);
                })
                ->with(['projectRecords.zone.pattern', 'projectRecords.project', 'projectRecords.zone', 'projectRecords.shift'])
                ->chunk(100, function ($employees) use (&$exportData, $today, $targetDays) {
                    foreach ($employees as $employee) {
                        $record = $employee->projectRecords
                            ->where('status', 1)
                            ->filter(fn ($r) => (! $r->end_date || $r->end_date >= $today) && $r->start_date <= $today)
                            ->sortByDesc('start_date')
                            ->first();

                        if (! $record || ! $record->zone || ! $record->zone->pattern) {
                            continue;
                        }

                        $pattern = $record->zone->pattern;

                        $workDays = collect();
                        $checkDate = $today->copy();
                        while ($workDays->count() < $targetDays) {
                            $dayIndex = $checkDate->dayOfWeekIso;
                            $isWorking = $pattern->{'day_'.$dayIndex} ?? false;
                            if ($isWorking) {
                                $workDays->push($checkDate->copy());
                            }
                            $checkDate->subDay();
                        }

                        $absentConsecutively = $workDays->every(function ($day) use ($employee) {
                            return ! Attendance::where('employee_id', $employee->id)
                                ->whereDate('date', $day)
                                ->exists();
                        });

                        if ($absentConsecutively) {
                            $exportData->push([
                                $employee->full_name,
                                $employee->id,
                                $employee->national_id,
                                $employee->mobile_number,
                                optional($record->project)->name,
                                optional($record->zone)->name,
                                optional($record->shift)->name,
                                $targetDays,
                                $workDays->first()->toDateString(),
                            ]);
                        }
                    }
                });

            if ($exportData->isNotEmpty()) {
                $fileName = 'consecutive_absences_'.now()->format('Y_m_d_His').'.xlsx';
                $filePath = 'exports/'.$fileName;
                Excel::store(new ConsecutiveAbsenceExport($exportData), $filePath, 'local');

                $emails = [
                    // 'legal@artalgroup.net',
                    // 'admin2@artalgroup.net',
                    // 'sultan@artalgroup.net',
                    // 'hradmin@artalgroup.net',
                    'mohammedalrabeai@gmail.com',
                ];

                foreach ($emails as $email) {
                    Mail::to($email)->queue(new ConsecutiveAbsenceReportMail($filePath));
                }
                $otpService = new OtpService;
                $phone = '966571718153';
                $message = 'تقرير الغياب المتتالي للموظفين مرفق في البريد الإلكتروني.';
                $otpService->sendOtp($phone, $message);
                $this->info('تم إرسال التقرير إلى: '.implode(', ', $emails));
            } else {
                $this->info('لا يوجد موظفون تغيبوا بشكل متتالي.');
            }
        } catch (\Throwable $e) {
            \Log::error('فشل تنفيذ تقرير الغياب المتتالي: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $otpService = new OtpService;
            $phone = '966571718153';
            $message = 'فشل تنفيذ تقرير الغياب المتتالي: '.$e->getMessage();
            $otpService->sendOtp($phone, $message);
        }
    }
}
