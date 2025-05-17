<?php

// namespace App\Filament\Widgets;

// use App\Models\Attendance;
// use Filament\Widgets\Widget;
// use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

// class AttendanceReportWidget extends Widget
// {
//     use HasWidgetShield;
//     protected static ?string $heading = 'Attendance Reports';

//     protected static ?int $sort = 1;

//     protected static string $view = 'filament.widgets.attendance-report-widget';

//     public function getViewData(): array
//     {
//         $today = now()->toDateString();

//         return [
//             'present_count' => Attendance::where('status', 'present')->whereDate('created_at', $today)->count(),
//             'absent_count' => Attendance::where('status', 'absent')->whereDate('created_at', $today)->count(),
//             'leave_count' => Attendance::where('status', 'leave')->whereDate('created_at', $today)->count(),
//         ];
//     }
// }
