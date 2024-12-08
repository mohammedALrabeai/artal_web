<?php

// namespace App\Filament\Widgets;

// use App\Models\Zone;
// use Filament\Widgets\PieChartWidget;

// class EmployeeZones extends PieChartWidget
// {
//     protected static ?string $heading = 'Employees by Zone';

//     protected function getData(): array
//     {
//         $zones = Zone::withCount('employees')->get();

//         return [
//             'datasets' => [
//                 [
//                     'label' => __('Employees by Zone'),
//                     'data' => $zones->pluck('employees_count')->toArray(),
//                 ],
//             ],
//             'labels' => $zones->pluck('name')->toArray(),
//         ];
//     }
// }
