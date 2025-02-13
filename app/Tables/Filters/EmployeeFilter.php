<?php

namespace App\Tables\Filters;

use App\Models\Employee;
use Filament\Forms;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class EmployeeFilter extends Filter
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Employee'));

        // إضافة حقل Select متعدد الاختيار وقابل للبحث
        $this->form([
            Forms\Components\Select::make('employees')
                ->label(__('Employees'))
                ->multiple()
                ->searchable()
                ->getSearchResultsUsing(function (string $search) {
                    return Employee::query()
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('father_name', 'like', "%{$search}%")
                        ->orWhere('grandfather_name', 'like', "%{$search}%")
                        ->orWhere('family_name', 'like', "%{$search}%")
                        ->orWhere('national_id', 'like', "%{$search}%")
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(function ($employee) {
                            return [
                                $employee->id => $employee->first_name.' '.$employee->father_name.' '.$employee->grandfather_name.' '.$employee->family_name,
                            ];
                        });
                })
                ->getOptionLabelUsing(function ($value) {
                    return Employee::find($value)?->full_name;
                })
                ->placeholder(__('Select employees')),
        ]);

        // تطبيق الفلتر على الاستعلام
        $this->query(function (Builder $query, array $data) {
            if (! empty($data['employees'])) {
                $query->whereIn('employee_id', $data['employees']);
            }
        });
    }
}
