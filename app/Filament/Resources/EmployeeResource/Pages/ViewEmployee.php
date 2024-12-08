<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;
    // protected static string $view = 'filament.resources.employee-resource.pages.view-employee';


    public static function getNavigationLabel(): string
    {
        return __('View Employee');
    }

    public function getTitle(): string
    {
        return __('View Employee');
    }
}
