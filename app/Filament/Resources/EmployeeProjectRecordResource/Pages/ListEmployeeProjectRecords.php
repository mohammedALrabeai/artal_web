<?php

namespace App\Filament\Resources\EmployeeProjectRecordResource\Pages;

use App\Filament\Resources\EmployeeProjectRecordResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;

class ListEmployeeProjectRecords extends ListRecords
{
    protected static string $resource = EmployeeProjectRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            // ExportAction::make(),
            ExportAction::make()
                ->label('تصدير الجميع ')
                ->action(fn () => (new \App\Exports\EmployeeProjectRecordsExport)->download('employee_project_records.xlsx'))
                ->color('success'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make(__('النشط'))
                ->modifyQueryUsing(function ($query) {
                    // عرض السجلات النشطة فقط (مثلاً الحالة "active")
                    return $query->where('status', true);
                }),

            'all' => Tab::make(__('جميع مواقع الموظفين'))
                ->modifyQueryUsing(function ($query) {
                    // عرض جميع السجلات بدون تصفية
                    return $query;
                }),
        ];
    }

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()->latest();
    }
}
