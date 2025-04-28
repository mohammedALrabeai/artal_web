<?php

namespace App\Filament\Resources\ShiftShortageResource\Pages;

use App\Exports\ShiftShortagesExport;
use App\Filament\Resources\ShiftShortageResource;
use App\Filament\Resources\ShiftShortageResource\Widgets\ShiftEmployeeShortageChart;
use App\Filament\Resources\ShiftShortageResource\Widgets\ShiftEmployeeShortageOverview;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListShiftShortages extends ListRecords
{
    protected static string $resource = ShiftShortageResource::class;

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Actions\CreateAction::make(),
    //     ];
    // }
    protected function getHeaderWidgets(): array
    {
        return [
            ShiftEmployeeShortageOverview::class,
            // ShiftEmployeeShortageChart::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_excel')
                ->label('تصدير إلى Excel')
                ->action(function () {
                    return Excel::download(new ShiftShortagesExport($this->getFilteredTableQuery()), 'ShiftShortagesExport.xlsx');
                }),
        ];
    }
}
