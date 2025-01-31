<?php

namespace App\Filament\Resources\ShiftShortageResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ShiftShortageResource;
use App\Filament\Resources\ShiftShortageResource\Widgets\ShiftEmployeeShortageChart;
use App\Filament\Resources\ShiftShortageResource\Widgets\ShiftEmployeeShortageOverview;

class ListShiftShortages extends ListRecords
{
    protected static string $resource = ShiftShortageResource::class;

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Actions\CreateAction::make(),
    //     ];
    // }
        protected  function getHeaderWidgets(): array
{
    return [
        ShiftEmployeeShortageOverview::class,
        // ShiftEmployeeShortageChart::class,
    ];
}
}
