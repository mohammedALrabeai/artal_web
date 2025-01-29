<?php

namespace App\Filament\Resources\ShiftShortageResource\Widgets;
// namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Shift;
use App\Models\EmployeeProjectRecord;

class ShiftEmployeeShortageChart extends ChartWidget
{
    protected static ?string $heading = 'نقص الموظفين لكل وردية';
    protected static string $chartType = 'bar';

    protected function getType(): string
    {
        return static::$chartType;
    }

    protected function getData(): array
    {
        $shifts = Shift::all();

        $labels = $shifts->pluck('name')->toArray();
        $shortages = $shifts->map(fn ($shift) => 
            max(0, $shift->emp_no - EmployeeProjectRecord::where('shift_id', $shift->id)
                ->where('status', 1)
                ->count())
        )->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'عدد الموظفين الناقصين',
                    'data' => $shortages,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
