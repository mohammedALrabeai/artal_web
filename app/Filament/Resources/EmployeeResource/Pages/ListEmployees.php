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
            ->label('Import Employees')
            ->form([
                Forms\Components\FileUpload::make('employee_file')
                    ->label('Upload Excel File')
                    ->disk('public') // تأكد من إعداد المسار
                    ->directory('uploads') // مسار حفظ الملف
                    ->preserveFilenames()
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel']) // ملفات Excel فقط
                    ->required(),
            ])
            ->action(function (array $data) {
                $filePath = storage_path('app/public/' . $data['employee_file']);
                //    dd($filePath);
                // التحقق من مسار الملف
                    if (!file_exists($filePath)) {
                        dd("الملف غير موجود في المسار المحدد!");
                        Notification::make()
                        ->title("danger")
                        ->body("الملف غير موجود في المسار المحدد!")
                        ->danger();
                        // Filament::notify('danger', 'الملف غير موجود في المسار المحدد: ' . $filePath);
                        return;
                    }
                Log::info('File Path: '. $filePath);

                // استيراد البيانات من الملف باستخدام Laravel Excel
                $rows = \Maatwebsite\Excel\Facades\Excel::toArray(null, $filePath);
dd($rows);
                // طباعة الأعمدة والقيم
                foreach ($rows as $sheet) {
                    foreach ($sheet as $row) {
                        Log::info('Row Data:', $row);
                        // logger()->info('Row Data:', $row); // تسجيل البيانات في السجلات
                    }
                }

                Notification::make()
            ->title("success")
            ->body("تم تحليل البيانات وعرضها في السجل (log)!")
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
    ];
}

}
