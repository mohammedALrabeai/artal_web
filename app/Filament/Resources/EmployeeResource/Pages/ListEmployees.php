<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use Filament\Actions;
use Filament\Forms;

use Illuminate\Support\Facades\URL;
use Filament\Resources\Components\Tab;
use Tables\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\EmployeeResource;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
// use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;


class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ExportAction::make(),
            Actions\Action::make('exportAttendance')
            ->label('Export Attendance')
            ->form([
                Forms\Components\DatePicker::make('start_date')
                    ->label('Start Date')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->label('End Date')
                    ->required(),
            ])
            ->action(function (array $data) {
                $url = URL::temporarySignedRoute(
                    'export.attendance',
                    now()->addMinutes(5),
                    [
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                    ]
                );

                return redirect($url);
            }),
            // Actions\Action::make('exportAll')
            // ->label(__('Export All'))
            // ->icon('heroicon-o-arrow-down-tray') // اختيار أيقونة مناسبة
            // ->color('primary')
            // ->action(function () {
            //     return ExcelExport::make()
            //         ->query(\App\Models\Employee::query()) // تحديد استعلام قاعدة البيانات
            //         ->columns([
            //             'first_name' => __('First Name'),
            //             'family_name' => __('Family Name'),
            //             'national_id' => __('National ID'),
            //             'job_status' => __('Job Status'),
            //             'email' => __('Email'),
            //         ])
            //         ->filename('all_employees.pdf')
            //         ->pdf(); // التصدير إلى PDF
            // }),
        ];
    }

//     protected  function getHeaderWidgets(): array
// {
//     return [
//         \App\Filament\Resources\EmployeeResource\Widgets\ExportEmployeesWidget::class,
//     ];
// }


public function getTabs(): array
{
    return [
        'all' => Tab::make(__('All Employees'))
            ->modifyQueryUsing(function ($query) {
                return $query; // عرض جميع الموظفين
            }),

        'with_insurance' => Tab::make(__('With Insurance'))
            ->modifyQueryUsing(function ($query) {
                return $query->whereNotNull('insurance_company_id'); // الموظفين مع التأمين
            }),

        'without_insurance' => Tab::make(__('Without Insurance'))
            ->modifyQueryUsing(function ($query) {
                return $query->whereNull('insurance_company_id'); // الموظفين بدون التأمين
            }),
    ];
}

}
