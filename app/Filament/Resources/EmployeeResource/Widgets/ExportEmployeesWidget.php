<?php

namespace App\Filament\Resources\EmployeeResource\Widgets;

// namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Pxlrbt\FilamentExcel\Exports\ExcelExport;

class ExportEmployeesWidget extends Widget
{
    // protected static string $view = 'filament.widgets.export-employees-widget';
    protected static string $view = 'filament.resources.employee-resource.widgets.export-employees-widget';


    public function exportAll()
    {
        return ExcelExport::make()
            ->table('employees') // اسم الجدول في قاعدة البيانات
            ->columns([
                'first_name' => __('First Name'),
                'family_name' => __('Family Name'),
                'national_id' => __('National ID'),
                'job_status' => __('Job Status'),
                'email' => __('Email'),
            ])
            ->filename('all_employees.pdf')
            ->pdf(); // تصدير كـ PDF
    }
}
