<?php

namespace App\Console\Commands;

use App\Models\Shift;
use App\Models\ShiftSlot;
use App\Models\EmployeeProjectRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignShiftSlots extends Command
{
    protected $signature = 'assign:shift-slots';
    protected $description = 'توليد shift_slots وربط الإسنادات النشطة بها بشكل آمن';

    public function handle(): int
    {
        DB::transaction(function () {
            logger('🚀 بدأ تنفيذ تخصيص الأماكن وربط الموظفين (فقط سجل الملاحظات المهمة)');

            // 1. توليد الأماكن لكل وردية حسب emp_no
            Shift::all()->each(function ($shift) {
                $count = (int) $shift->emp_no;

                if ($count <= 0) {
                    logger()->warning("⚠️ وردية بدون emp_no → الوردية {$shift->id} ({$shift->name})");
                    return;
                }

                for ($i = 1; $i <= $count; $i++) {
                    ShiftSlot::firstOrCreate([
                        'shift_id' => $shift->id,
                        'slot_number' => $i,
                    ]);
                }
            });

            // 2. ربط الموظفين النشطين بـ shift_slot المتاحة
            $records = EmployeeProjectRecord::query()
                ->where('status', true)
                ->whereNull('end_date')
                ->whereNull('shift_slot_id')
                ->get();

            foreach ($records as $record) {
                $shift = $record->shift;

                if (! $shift) {
                    logger()->warning("❗ لا توجد وردية مرتبطة → employee_id={$record->employee_id}, record_id={$record->id}");
                    continue;
                }

                $required = (int) $shift->emp_no;

                if ($required <= 0) {
                    logger()->warning("❗ وردية بلا emp_no → shift_id={$shift->id}, employee_id={$record->employee_id}, record_id={$record->id}");
                    continue;
                }

                $usedSlotIds = EmployeeProjectRecord::where('shift_id', $record->shift_id)
                    ->where('status', true)
                    ->whereNull('end_date')
                    ->whereNotNull('shift_slot_id')
                    ->pluck('shift_slot_id')
                    ->toArray();

                $availableSlot = ShiftSlot::where('shift_id', $record->shift_id)
                    ->whereNotIn('id', $usedSlotIds)
                    ->orderBy('slot_number')
                    ->first();

                if (! $availableSlot) {
                    logger()->error("🚫 لا يوجد مكان متاح → employee_id={$record->employee_id}, shift_id={$record->shift_id}, record_id={$record->id}");
                    logger()->info("    👀 تحقق من: emp_no < عدد الإسنادات الحالية أو تجاوز في الربط.");
                }
            }

            logger('📝 تم تنفيذ المهمة بنجاح مع تسجيل جميع الملاحظات فقط.');
        });

        return self::SUCCESS;
    }
}
