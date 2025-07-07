<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use Filament\Forms;
use Filament\Actions;
use App\Models\Employee;
use App\Models\Exclusion;
use Tables\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Filament\Resources\Components\Tab;
use Filament\Notifications\Notification;
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
            Actions\Action::make('importEmployees')
                ->visible(fn () => auth()->user()?->can('create_employee'))
                ->label(__('Import Employees'))
                ->form([
                    Forms\Components\FileUpload::make('employee_file')
                        ->label(__('Upload Excel File'))
                        ->disk('public') // تأكد من إعداد المسار
                        ->directory('uploads') // مسار حفظ الملف
                        // ->preserveFilenames()
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel']) // ملفات Excel فقط
                        ->required(),
                    Forms\Components\Checkbox::make('use_ids_from_file')
                        ->label(__('Use IDs from file'))
                        ->default(false),
                ])
                ->action(function (array $data) {
                    $filePath = storage_path('app/public/uploads/'.basename($data['employee_file']));
                    $useIdsFromFile = $data['use_ids_from_file'];
                    if (! file_exists($filePath)) {
                        Filament::notify('danger', 'الملف غير موجود: '.$filePath);

                        return;
                    }

                    \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\EmployeesImport($useIdsFromFile, auth()->user()->id), $filePath);

                    Notification::make()
                        ->title(__('success'))
                        ->body(__('تم تحليل البيانات وعرضها في السجل (log)!'))
                        ->success();
                    // Filament::notify('success', 'تم تحليل البيانات وعرضها في السجل (log)!');
                })
                ->color('success'),
            Actions\Action::make('exportEmployees')
                ->label(__('Export Employees'))
                ->color('warning')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    Forms\Components\Select::make('tab')
                        ->label('Select Tab')
                        ->options([
                            'all' => __('All Employees'),
                            'with_insurance' => __('With Insurance'),
                            'without_insurance' => __('Without Insurance'),
                            'unassigned_employees' => __('Unassigned Employees'),
                            'assigned_employees' => __('Assigned Employees'),
                            'onboarding_employees' => __('Onboarding Employees'),
                            'excluded_employees' => __('Excluded Employees'),
                        ])
                        ->default('all')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $query = \App\Models\Employee::query();

                    // تطبيق الفلتر حسب التبويبة
                    match ($data['tab']) {
                        'with_insurance' => $query->whereNotNull('commercial_record_id'),
                        'without_insurance' => $query->whereNull('commercial_record_id'),
                        'unassigned_employees' => $query->active()->whereDoesntHave('projectRecords'),

                        'assigned_employees' => $query->whereHas('currentZone'),
                        'onboarding_employees' => $query->whereHas('currentZone')->whereDoesntHave('attendances', fn ($q) => $q->where('status', 'present')),
                        'excluded_employees' => $query->whereHas('exclusions', fn ($q) => $q->where('status', \App\Models\Exclusion::STATUS_APPROVED)),
                        default => $query,
                    };

                    return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\EmployeesExport($query), 'employees_export.xlsx');
                }),

            ExportAction::make()
                ->visible(fn () => auth()->user()?->hasRole('super_admin')),
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
                        'export.enhanced.attendance2',
                        now()->addMinutes(5),
                        [
                            'start_date' => $data['start_date'],
                            'end_date' => $data['end_date'],
                        ]
                    );

                    return redirect($url);
                })
                ->modalSubmitActionLabel('تصدير'),

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
                    return $query
                        ->active() // 🔥 الموظفين النشطين فقط
                        ->whereDoesntHave('projectRecords'); // 🔥 الذين لا يملكون أي سجل إسناد
                }),

            'assigned_employees' => Tab::make(__('Assigned Employees'))
                ->modifyQueryUsing(function ($query) {
                    // استخدام علاقة currentZone للتأكد من وجود سجل تعيين نشط
                    return $query->whereHas('currentZone');
                }),

            // ✅ **إضافة تبويب "الموظفون قيد المباشرة"**
            'onboarding_employees' => Tab::make(__('Onboarding Employees'))
                ->modifyQueryUsing(fn ($query) => $query->
                    active() // ✅ الموظفون النشطون فقط
                        ->whereHas('currentZone') // ✅ الموظفون الذين لديهم موقع مسند إليهم
                        ->whereDoesntHave('attendances', fn ($q) => $q->where('status', 'present')) // ✅ لا يوجد لهم أي تحضير بحالة "حضور"
                ),
            // ✅ **إضافة تبويب الموظفين المستبعدين**
            'excluded_employees' => Tab::make(__('Excluded Employees'))
                ->modifyQueryUsing(fn ($query) => $query->whereHas('exclusions', fn ($q) => $q->where('status', Exclusion::STATUS_APPROVED)
                )),

        ];
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
{
    return Employee::query()
        ->with(['latestZone.zone']) // تحميل علاقة latestZone والـ zone التابعة لها
        ->latest(); // ترتيب حسب created_at
}

}
