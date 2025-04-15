<?php

namespace App\Filament\Resources\EmployeeProjectRecordResource\Pages;

use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Resources\Components\Tab;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use App\Filament\Resources\EmployeeProjectRecordResource;

class ListEmployeeProjectRecords extends ListRecords
{
    protected static string $resource = EmployeeProjectRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            // ExportAction::make(),
            ExportAction::make('export_with_options')
                ->label('تصدير الموظفين')
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    Select::make('status')
                        ->label('نوع السجلات')
                        ->options([
                            'active' => 'الموظفين النشطين فقط',
                            'all' => 'جميع الموظفين',
                        ])
                        ->default('active')
                        ->required(),
                    DatePicker::make('start_date')
                        ->label('تاريخ البداية')
                        ->default(now())
                        ->required(),
                ])
                ->action(function (array $data) {
                    $onlyActive = $data['status'] === 'active';
                    $startDate = $data['start_date'];

                    return \Maatwebsite\Excel\Facades\Excel::download(
                        new \App\Exports\EmployeeProjectRecordsExport($onlyActive, $startDate),
                        'employee_project_records.xlsx'
                    );
                })
                ->requiresConfirmation()
                ->modalHeading('تأكيد التصدير')
                ->modalDescription('هل تريد تصدير الموظفين النشطين فقط أم جميعهم؟')
                // ->deselectRecordsAfterCompletion()
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
