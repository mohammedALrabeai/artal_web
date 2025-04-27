<?php

namespace App\Filament\Resources\ShiftShortageResource\Widgets;

// namespace App\Filament\Widgets;

use App\Models\Shift;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class ShiftEmployeeShortageOverview extends StatsOverviewWidget
{
    protected function getCards(): array
    {
        // قراءة قيمة الفلتر من الطلب الحالي
        $projectStatus = request()->input('tableFilters.project_status.value', 'active');

        $query = Shift::query();

        // فلترة المشاريع حسب الفلتر المختار
        if ($projectStatus === 'inactive') {
            $query->whereHas('zone.project', function ($q) {
                $q->where('status', false);
            });
        } elseif ($projectStatus === 'active' || is_null($projectStatus)) {
            $query->whereHas('zone.project', function ($q) {
                $q->where('status', true);
            });
        }

        // فلترة المواقع النشطة
        $query->whereHas('zone', function ($q) {
            $q->where('status', true);
        });

        // فلترة الورديات النشطة (لو عندك حقل status)
        // $query->where('status', true);

        // جلب الورديات مع عدد الموظفين المسندين لكل وردية دفعة وحدة
        $shifts = $query->withCount([
            'employeeProjectRecords as assigned_count' => function ($q) {
                $q->where('status', 1);
            },
        ])->get();

        // حساب إجمالي النقص
        $totalShortage = $shifts->sum(function ($shift) {
            return max(0, $shift->emp_no - $shift->assigned_count);
        });

        return [
            Card::make('إجمالي نقص الموظفين في جميع الورديات', $totalShortage)
                ->color($totalShortage > 0 ? 'danger' : 'success')
                ->description($totalShortage > 0 ? 'هناك نقص في الموظفين يجب تغطيته!' : 'كل الورديات مكتملة ✅'),
        ];
    }
}
