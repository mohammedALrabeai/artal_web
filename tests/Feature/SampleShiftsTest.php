<?php

namespace Tests\Unit;

use App\Models\Shift;
use Tests\TestCase;

class SampleShiftsTest extends TestCase
{
    /** @test */
    public function print_sample_shifts_by_type()
    {
        $types = ['morning', 'evening', 'morning_evening', 'evening_morning'];

        $sampleShifts = [];

        foreach ($types as $type) {
            $shift = Shift::where('type', $type)
                ->where('status', 1)
                ->whereHas('zone.pattern') // التحقق من وجود نمط عمل
                ->orderBy('start_date') // اختيار الأقدم كمرجع
                ->first();

            if ($shift) {
                $sampleShifts[] = [
                    'id' => $shift->id,
                    'type' => $shift->type,
                    'start_date' => $shift->start_date,
                    'morning_start' => $shift->morning_start,
                    'morning_end' => $shift->morning_end,
                    'evening_start' => $shift->evening_start,
                    'evening_end' => $shift->evening_end,
                    'zone' => $shift->zone?->name,
                ];
            }
        }

        dd($sampleShifts);
    }
}
