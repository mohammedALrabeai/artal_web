<?php

namespace App\Filament\Resources\ShiftShortageResource\Widgets;

// namespace App\Filament\Widgets;

use App\Models\EmployeeProjectRecord;
use App\Models\Shift;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class ShiftEmployeeShortageOverview extends StatsOverviewWidget
{
    protected function getCards(): array
    {
        // قراءة قيمة الفلتر المختارة من الطلب الحالي
        $projectStatus = request()->input('tableFilters.project_status.value', 'active');

        // تجهيز الاستعلام مع الفلترة المناسبة
        $query = Shift::query();

        if ($projectStatus === 'inactive') {
            $query->whereHas('zone.project', function ($q) {
                $q->where('status', false);
            });
        } elseif ($projectStatus === 'active') {
            $query->whereHas('zone.project', function ($q) {
                $q->where('status', true);
            });
        }
        // لو 'all' لا نضيف أي شرط

        // جلب الورديات بعد الفلترة
        $shifts = $query->get();

        // حساب إجمالي النقص
        $totalShortage = $shifts->sum(function ($shift) {
            $assigned = EmployeeProjectRecord::where('shift_id', $shift->id)
                ->where('status', 1)
                ->count();

            return max(0, $shift->emp_no - $assigned);
        });

        return [
            Card::make('إجمالي نقص الموظفين في جميع الورديات', $totalShortage)
                ->color($totalShortage > 0 ? 'danger' : 'success')
                ->description($totalShortage > 0 ? 'هناك نقص في الموظفين يجب تغطيته!' : 'كل الورديات مكتملة ✅'),
        ];
    }
}
