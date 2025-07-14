<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\Employee;
use Filament\Pages\Page;
use App\Models\Exclusion;
use Livewire\WithPagination;
use App\Exports\EmployeesExport;
use Filament\Pages\Actions\Action;
    use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmployeeChangesExport;
use Filament\Notifications\Notification;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use App\Jobs\ExportWorkPatternPayrollJob; // <-- استيراد الـ Job

class OperationReports extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $title = 'تقارير العمليات';
    protected static ?string $navigationLabel = 'تقارير العمليات';
    protected static ?int $navigationSort = 95;

    protected static string $view = 'filament.pages.operation-reports';



public string $from = '';
public string $to = '';

public function exportChanges()
{
    if (! $this->from || ! $this->to || $this->from > $this->to) {
        Notification::make()
            ->title('خطأ في التواريخ')
            ->body('تأكد من إدخال فترة صحيحة.')
            ->danger()
            ->send();
        return;
    }

    $fileName = 'المتغيرات_' . $this->from . '_حتى_' . $this->to . '.xlsx';

    return response()->streamDownload(function () {
        echo Excel::raw(new EmployeeChangesExport($this->from, $this->to), \Maatwebsite\Excel\Excel::XLSX);
    }, $fileName);
}


 protected function getHeaderActions(): array
    {
        return [
            // Action::make('exportWorkPatternPayroll')
            //     ->label('تصدير التقرير الشهري لجميع المشاريع')
            //     ->icon('heroicon-o-arrow-down-tray')
            //     ->color('primary')
            //     ->action(function () {
            //         $projectIds = \App\Models\Project::where('status', true)->pluck('id')->toArray();
            //         $currentDate = now()->format('Y-m-d');
            //         $user = Auth::user();

            //         // 1. إرسال إشعار "جاري المعالجة" الفوري
            //         Notification::make()
            //             ->title('طلبك قيد المعالجة')
            //             ->body('لقد بدأنا في إعداد تقرير جدول التشغيل. سنقوم بإعلامك فور انتهائه.')
            //             ->info()
            //             ->send(); // ->send() كافية هنا لإظهارها للمستخدم الحالي

            //         // 2. إرسال الـ Job إلى قائمة الانتظار
            //         ExportWorkPatternPayrollJob::dispatch($projectIds, $currentDate, $user->id);
            //     }),
        ];
    }


     public function exportWorkPatternPayroll(): Action
    {
        return Action::make('exportWorkPatternPayroll')
            ->label('تصدير التقرير الشهري لجميع المشاريع')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('primary')
            ->action(function () {
                $projectIds = \App\Models\Project::where('status', true)->pluck('id')->toArray();
                $currentDate = now()->format('Y-m-d');
                $user = Auth::user();

                Notification::make()
                ->title('تم استلام طلبك بنجاح')
                ->body('جاري تجهيز تقرير جدول التشغيل. سيصلك إشعار آخر هنا يحتوي على رابط التنزيل فور اكتماله.')
                ->info()
                ->send();

                ExportWorkPatternPayrollJob::dispatch($projectIds, $currentDate, $user->id);
            });
    }


       public function exportAllEmployees(): Action
{
    return Action::make('exportAllEmployees')
        ->label('تصدير الموظفين')
        ->icon('heroicon-o-users')
        ->color('success')
        // ✅ نموذج يختار التبويب
        ->form([
            Forms\Components\Select::make('tab')
                ->label('اختر الفئة')
                ->options([
                    'all'                 => 'كل الموظفين',
                    'with_insurance'      => 'مع التأمين',
                    'without_insurance'   => 'بدون التأمين',
                    'unassigned'          => 'غير مسندين',
                    'assigned'            => 'مسندين',
                    'onboarding'          => 'قيد المباشرة',
                    'excluded'            => 'مستبعدون',
                ])
                ->default('all')
                ->required(),
        ])
        ->action(function (array $data) {

            // بناء الاستعلام حسب الخيار المختار
            $query = Employee::query();
            match ($data['tab']) {
                'with_insurance'    => $query->whereNotNull('commercial_record_id'),
                'without_insurance' => $query->whereNull('commercial_record_id'),
                'unassigned'        => $query->active()->whereDoesntHave('projectRecords'),
                'assigned'          => $query->whereHas('currentZone'),
                'onboarding'        => $query->active()
                                             ->whereHas('currentZone')
                                             ->whereDoesntHave('attendances',
                                                fn ($q) => $q->whereIn('status', ['present','coverage'])
                                             ),
                'excluded'          => $query->whereHas('exclusions',
                                                fn ($q) => $q->where('status', Exclusion::STATUS_APPROVED)),
                default             => $query,
            };

            $fileName = 'employees_'.now('Asia/Riyadh')->format('Y-m-d_H-i').'.xlsx';

            return Excel::download(new EmployeesExport($query), $fileName);
        });
}


}
