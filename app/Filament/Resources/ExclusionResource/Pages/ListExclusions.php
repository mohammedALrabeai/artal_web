<?php

namespace App\Filament\Resources\ExclusionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ExclusionResource;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use Maatwebsite\Excel\Excel;   // لاستخدام XLSX أو CSV



class ListExclusions extends ListRecords
{
    protected static string $resource = ExclusionResource::class;

    protected function getHeaderActions(): array
    {
        return [

             ExportAction::make('export')
                ->label(__('Export Excel'))
                ->icon('heroicon-o-arrow-down-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()                                 // يلتقط الأعمدة والفلاتر الظاهرة
                        ->withFilename(fn () => 'exclusions-' . now()->format('Y-m-d'))
                        ->withWriterType(Excel::XLSX),               // أو Excel::CSV
                ]),
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()->latest();
    }
}
