<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        LeaveType::insert([
            ['name' => 'إجازة سنوية مدفوعة',        'code' => 'PV', 'is_paid' => true],
            ['name' => 'إجازة بدون أجر غير مدفوعة', 'code' => 'UV', 'is_paid' => false],
            ['name' => 'إجازة مرضية مدفوعة',         'code' => 'SL', 'is_paid' => true],
            ['name' => 'إجازة مرضية غير مدفوعة',     'code' => 'UL', 'is_paid' => false],
            ['name' => 'إجازة وفاة مدفوعة',          'code' => 'PV', 'is_paid' => true],
            ['name' => 'إجازة زواج مدفوعة',          'code' => 'PV', 'is_paid' => true],
            ['name' => 'إجازة مولود مدفوعة',         'code' => 'PV', 'is_paid' => true],
            ['name' => 'إجازة عيد الأضحى مدفوعة',    'code' => 'PV', 'is_paid' => true],
            ['name' => 'إجازة عيد الفطر مدفوعة',     'code' => 'PV', 'is_paid' => true],
            ['name' => 'إجازة يوم التأسيس مدفوعة',   'code' => 'PV', 'is_paid' => true],
            ['name' => 'إجازة اليوم الوطني مدفوعة',  'code' => 'PV', 'is_paid' => true],
        ]);
    }
}
