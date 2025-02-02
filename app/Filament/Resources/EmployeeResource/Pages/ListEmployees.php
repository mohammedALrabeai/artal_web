<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use Filament\Forms;
use Filament\Actions;

use Tables\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\URL;
use Filament\Resources\Components\Tab;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\EmployeeResource;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use Illuminate\Support\Facades\Log;
// use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;


class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('importEmployees')
            ->label(__('Import Employees'))
            ->form([
                Forms\Components\FileUpload::make('employee_file')
                    ->label(__('Upload Excel File'))
                    ->disk('public') // تأكد من إعداد المسار
                    ->directory('uploads') // مسار حفظ الملف
                    ->preserveFilenames()
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel']) // ملفات Excel فقط
                    ->required(),
                    Forms\Components\Checkbox::make('use_ids_from_file')
                    ->label(__('Use IDs from file'))
                    ->default(false),
            ])
            
            ->action(function (array $data) {
                $filePath = storage_path('app/public/uploads/' . basename($data['employee_file']));
                $useIdsFromFile = $data['use_ids_from_file'];
                if (!file_exists($filePath)) {
                    Filament::notify('danger', 'الملف غير موجود: ' . $filePath);
                    return;
                }

                \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\EmployeesImport($useIdsFromFile), $filePath);

                Notification::make()
            ->title(__('success'))
            ->body(__('تم تحليل البيانات وعرضها في السجل (log)!'))
            ->success();
                // Filament::notify('success', 'تم تحليل البيانات وعرضها في السجل (log)!');
            })
            ->color('success'),
            ExportAction::make(),
            // Actions\Action::make('exportAttendance')
            // ->label('Export Attendance')
            // ->form([
            //     Forms\Components\DatePicker::make('start_date')
            //         ->label('Start Date')
            //         ->required(),
            //     Forms\Components\DatePicker::make('end_date')
            //         ->label('End Date')
            //         ->required(),
            // ])
            // ->action(function (array $data) {
            //     $url = URL::temporarySignedRoute(
            //         'export.attendance',
            //         now()->addMinutes(5),
            //         [
            //             'start_date' => $data['start_date'],
            //             'end_date' => $data['end_date'],
            //         ]
            //     );

            //     return redirect($url);
            // }),

            Actions\Action::make('exportAttendance2')
    ->label(__('Export Attendance'))
    ->form([
        Forms\Components\DatePicker::make('start_date')
            ->label(__('Start Date'))
            ->required(),
        Forms\Components\DatePicker::make('end_date')
            ->label(__('End Date'))
            ->required(),
    ])
    ->action(function (array $data) {
        $url = URL::temporarySignedRoute(
            'export.attendance2',
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
                return $query->whereNotNull('commercial_record_id'); // الموظفين مع التأمين
            }),

        'without_insurance' => Tab::make(__('Without Insurance'))
            ->modifyQueryUsing(function ($query) {
                return $query->whereNull('commercial_record_id'); // الموظفين بدون التأمين
            }),
        //     'unassigned_employees' => Tab::make(__('Unassigned Employees'))
        //     ->modifyQueryUsing(function ($query) {
        //         return $query->whereDoesntHave('zones'); // الموظفين غير المسندين إلى أي موقع
        //     }),

        // 'assigned_employees' => Tab::make(__('Assigned Employees'))
        //     ->modifyQueryUsing(function ($query) {
        //         return $query->whereHas('zones'); // الموظفين المسندين إلى مواقع
        //     }),
        'unassigned_employees' => Tab::make(__('Unassigned Employees'))
        ->modifyQueryUsing(function ($query) {
            // استخدام علاقة currentZone للتأكد من عدم وجود سجل تعيين نشط
            return $query->whereDoesntHave('currentZone');
        }),

    'assigned_employees' => Tab::make(__('Assigned Employees'))
        ->modifyQueryUsing(function ($query) {
            // استخدام علاقة currentZone للتأكد من وجود سجل تعيين نشط
            return $query->whereHas('currentZone');
        }),
    ];
}

}
