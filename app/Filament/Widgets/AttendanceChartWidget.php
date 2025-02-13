<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\PieChartWidget;
use Illuminate\Support\Facades\Auth;

class AttendanceChartWidget extends PieChartWidget
{
    use HasWidgetShield;

    protected static ?string $heading = 'Attendance Chart';

    protected function getData(): array
    {
        $today = now()->toDateString();

        return [
            'labels' => [
                __('Present'),
                __('Absent'),
                __('On Leave'),
            ],
            'datasets' => [
                [
                    'label' => __('Attendance'),
                    'data' => [
                        Attendance::where('status', 'present')->whereDate('created_at', $today)->count(),
                        Attendance::where('status', 'absent')->whereDate('created_at', $today)->count(),
                        Attendance::where('status', 'leave')->whereDate('created_at', $today)->count(),
                    ],
                    'backgroundColor' => [
                        '#4caf50', // لون الحاضرين
                        '#f44336', // لون الغياب
                        '#ffc107', // لون الإجازات
                    ],
                ],
            ],
        ];
    }

    // public static function canView(): bool
    // {
    //     return Auth::user()?->can('view_AttendanceChartWidget');
    // }
}
