<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class EmployeeMap extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    // protected static ?string $navigationIcon = 'heroicon-o-location-marker';
    protected static ?string $title = 'خريطة الموظف';
    protected static string $view = 'filament.pages.employee-map';

    public string $employeeId;

    public function mount(string $employeeId): void
    {
        // تخزين معرف الموظف
        $this->employeeId = $employeeId;
    }

    protected function getViewData(): array
    {
        return [
            'employeeId' => $this->employeeId,
        ];
    }
}
