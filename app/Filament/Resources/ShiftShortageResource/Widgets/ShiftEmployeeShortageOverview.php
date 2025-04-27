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
        $totalShortage = Shift::all()->sum(fn ($shift) => max(0, $shift->emp_no - EmployeeProjectRecord::where('shift_id', $shift->id)
            ->where('status', 1)
            ->count())
        );

        return [
            Card::make('إجمالي نقص الموظفين في جميع الورديات', $totalShortage)
                ->color($totalShortage > 0 ? 'danger' : 'success')
                ->description($totalShortage > 0 ? 'هناك نقص في الموظفين يجب تغطيته!' : 'كل الورديات مكتملة ✅'),
        ];
    }
}
