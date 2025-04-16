<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\URL;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('exportAttendance2')
                ->label(__('Export Attendance'))
                ->form([
                    Forms\Components\DatePicker::make('start_date')
                        ->label(__('Start Date'))
                        ->required(),
                    Forms\Components\DatePicker::make('end_date')
                        ->label(__('End Date'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    $url = URL::temporarySignedRoute(
                        'export.attendance2',
                        now()->addMinutes(5),
                        [
                            'start_date' => $data['start_date'],
                            'end_date' => $data['end_date'],
                        ]
                    );

                    return redirect($url);
                })
                ->modalSubmitActionLabel('تصدير'),
            ExportAction::make()
                ->visible(fn () => auth()->user()?->hasRole('super_admin')),
        ];
    }

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()->latest();
    }
}
