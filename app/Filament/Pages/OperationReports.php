<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Pages\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmployeeChangesExport;
use Filament\Notifications\Notification;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
    use Livewire\WithFileUploads;
use App\Jobs\ExportWorkPatternPayrollJob; // <-- استيراد الـ Job
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

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

}
