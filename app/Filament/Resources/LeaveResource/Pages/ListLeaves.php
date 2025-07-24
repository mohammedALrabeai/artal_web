<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use Filament\Actions;
use App\Filament\Resources\LeaveResource;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use Maatwebsite\Excel\Excel;   // لاستخدام XLSX أو CSV


class ListLeaves extends ListRecords
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
              ExportAction::make('export')
                ->label(__('Export Excel'))
                ->icon('heroicon-o-arrow-down-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()                                 // يلتقط الأعمدة والفلاتر الظاهرة
                        ->withFilename(fn () => 'Leaves-' . now()->format('Y-m-d'))
                        ->withWriterType(Excel::XLSX),               // أو Excel::CSV
                ]),
            Actions\CreateAction::make(),
            ExportAction::make(),
        ];
    }


      protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()->latest();
    }
}
