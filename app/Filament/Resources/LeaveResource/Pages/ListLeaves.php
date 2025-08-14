<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use Filament\Actions;
use App\Filament\Resources\LeaveResource;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
// use Maatwebsite\Excel\Excel;   // لاستخدام XLSX أو CSV
use Maatwebsite\Excel\Facades\Excel;

use App\Exports\LeavesExport;

use Filament\Forms;



class ListLeaves extends ListRecords
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //   ExportAction::make('export')
            //     ->label(__('Export Excel'))
            //     ->icon('heroicon-o-arrow-down-tray')
            //     ->exports([
            //         ExcelExport::make()
            //             ->fromTable()                                 // يلتقط الأعمدة والفلاتر الظاهرة
            //             ->withFilename(fn () => 'Leaves-' . now()->format('Y-m-d'))
            //             ->withWriterType(Excel::XLSX),               // أو Excel::CSV
            //     ]),
            Actions\CreateAction::make(),
           
            Actions\Action::make('exportLeaves')
                ->label(__('Export Leaves'))
                ->icon('heroicon-m-arrow-up-tray')
                // ->color('success')
                ->form([
                    Forms\Components\DatePicker::make('from')
                        ->label(__('From'))
                        ->closeOnDateSelection()
                        ->native(false),

                    Forms\Components\DatePicker::make('to')
                        ->label(__('To'))
                        ->closeOnDateSelection()
                        ->native(false),
                ])
                ->modalWidth('lg')
                ->action(function (array $data) {
                    $from = $data['from'] ?? null;
                    $to   = $data['to']   ?? null;

                    $filename = 'leaves_' . now('Asia/Riyadh')->format('Ymd_His') . '.xlsx';

                    return Excel::download(new LeavesExport($from, $to), $filename);
                }),
        ];
    }


      protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()->latest();
    }
}
