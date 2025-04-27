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
        $projectStatus = request()->input('tableFilters.project_status.value', 'active');
    
        // تجهيز استعلام الورديات
        $query = Shift::query()
            ->whereHas('zone', function ($q) use ($projectStatus) {
                $q->where('status', true)
                  ->whereHas('project', function ($q2) use ($projectStatus) {
                      if ($projectStatus === 'inactive') {
                          $q2->where('status', false);
                      } elseif ($projectStatus === 'active' || is_null($projectStatus)) {
                          $q2->where('status', true);
                      }
                  });
            });
    
        // جلب الورديات مع عدد الموظفين المسندين دفعة واحدة
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
            Card::make('إجمالي نقص الموظفين', $totalShortage)
                ->color($totalShortage > 0 ? 'danger' : 'success')
                ->description($totalShortage > 0 ? 'هناك نقص يجب تغطيته!' : 'كل الورديات مكتملة ✅')
                ->icon('heroicon-o-user-minus'),
        ];
    }
    
}
