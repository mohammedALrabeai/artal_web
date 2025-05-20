<?php

namespace Tests\Unit;

use Tests\TestCase;

class ShiftActivityTest extends TestCase
{
    /** @test */
    public function test_shift_currently_active_cases()
    {
        $testCases = [
            [385, '2025-05-05 08:30:00', true,  'morning - داخل وقت الصباح'],
            [385, '2025-05-05 17:00:00', false, 'morning - خارج وقت الصباح'],

            [301, '2025-05-05 23:30:00', true,  'evening - بداية الليل'],
            [301, '2025-05-06 06:30:00', true,  'evening - قبل نهاية الليل'],
            [301, '2025-05-06 08:00:00', false, 'evening - بعد نهاية الليل'],

            [104, '2025-05-05 08:30:00', false, 'morning_evening - دورة زوجية - صباح ❌'],
            [104, '2025-05-06 21:00:00', true,  'morning_evening - دورة زوجية - مساء'],

            [97, '2025-05-05 20:30:00', false, 'evening_morning - دورة زوجية - مساء ❌'],
            [97, '2025-05-06 07:30:00', false, 'evening_morning - دورة زوجية - صباح - قبل البداية ❌'],

            [301, '2025-05-09 06:30:00', true,  'evening - امتداد وردية من يوم عمل إلى يوم عطلة (قبل النهاية)'],
            [301, '2025-05-09 09:00:00', false, 'evening - امتداد وردية من يوم عمل إلى يوم عطلة (بعد النهاية)'],
        ];

        foreach ($testCases as [$shiftId, $datetime, $expected, $description]) {
            $shift = \App\Models\Shift::find($shiftId);
            $this->assertNotNull($shift, "⛔ Shift ID {$shiftId} not found");

            $now = \Carbon\Carbon::parse($datetime, 'Asia/Riyadh');
            [$result, $source] = $shift->getShiftActiveStatus2($now);

            echo "\n--- Test Case ---";
            echo "\nShift ID      : {$shift->id}";
            echo "\nDescription   : {$description}";
            echo "\nDatetime      : {$datetime}";
            echo "\nExpected      : ".($expected ? 'TRUE' : 'FALSE');
            echo "\nActual        : ".($result ? 'TRUE' : 'FALSE');
            echo "\nSource        : ".($source ?? 'N/A');
            echo "\nType          : {$shift->type}";
            echo "\nStart Date    : {$shift->start_date}";
            echo "\nMorning Start : {$shift->morning_start}";
            echo "\nMorning End   : {$shift->morning_end}";
            echo "\nEvening Start : {$shift->evening_start}";
            echo "\nEvening End   : {$shift->evening_end}";
            echo "\n";

            $this->assertSame(
                $expected,
                $result,
                "❌ {$description} failed. Expected ".($expected ? 'TRUE' : 'FALSE').' but got '.($result ? 'TRUE' : 'FALSE')
            );

            echo "✅ {$description} PASSED\n";
        }
    }
}
