<?php

namespace App\Console\Commands;

use App\Models\EmployeeProjectRecord;
use Illuminate\Console\Command;

class ReportUnassignedShiftSlots extends Command
{
    protected $signature = 'report:unassigned-shift-slots';
    protected $description = 'تقرير الموظفين المعلقين بدون سلوت مع اسم الموظف والموقع والوردية';

    public function handle(): int
    {
        $records = EmployeeProjectRecord::where('status', true)
            ->whereNull('end_date')
            ->whereNull('shift_slot_id')
            ->with(['employee', 'project', 'zone', 'shift'])
            ->get();

        if ($records->isEmpty()) {
            $this->info('🎉 لا يوجد موظفون معلقون بدون شاغر.');
            return self::SUCCESS;
        }

        $this->info('تقرير الموظفين المعلقين بدون شاغر:');
        $this->table(
            ['#', 'الموظف', 'رقم الهوية', 'المشروع', 'الموقع', 'الوردية', 'ID السجل'],
            $records->map(function ($rec, $i) {
                return [
                    $i + 1,
                    $rec->employee?->first_name . ' ' . $rec->employee?->father_name . ' ' . $rec->employee?->family_name,
                    $rec->employee?->national_id,
                    $rec->project?->name,
                    $rec->zone?->name,
                    $rec->shift?->name,
                    $rec->id,
                ];
            })->toArray()
        );

        // تخزين في اللوج أيضًا (اختياري)
        foreach ($records as $rec) {
            logger()->warning("🔗 موظف بدون شاغر: {$rec->employee?->first_name} {$rec->employee?->family_name}، رقم الهوية: {$rec->employee?->national_id}، الوردية: {$rec->shift?->name}، الموقع: {$rec->zone?->name}");
        }

        return self::SUCCESS;
    }
}
