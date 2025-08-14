<?php

namespace App\Filament\Resources\CoverageResource\Pages;

use App\Filament\Resources\CoverageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

use App\Exports\CoveragesExport;

use Filament\Forms;

use Maatwebsite\Excel\Facades\Excel;

class ListCoverages extends ListRecords
{
    protected static string $resource = CoverageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
             Actions\Action::make('exportCoverages')
                ->label(__('Export Coverages'))
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

                    $filename = 'coverages_' . now('Asia/Riyadh')->format('Ymd_His') . '.xlsx';

                    // تنزيل فوري
                    return Excel::download(new CoveragesExport($from, $to), $filename);
                }),
        ];
    }

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()->latest();
    }
}
