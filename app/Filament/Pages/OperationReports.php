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

}
