<?php

namespace App\Forms\Components;

use App\Models\Employee;
use Filament\Forms\Components\Select;

class EmployeeSelect extends Select
{
    public static function make(string $name = 'employee_id', bool $onlyWithoutActiveProject = false): static
    {
        return parent::make($name)
            ->label(__('Employee'))
            ->preload()
            ->searchable()
            ->placeholder(__('Search for an employee...'))
            ->options(fn () => self::getDefaultOptions($onlyWithoutActiveProject)) // تحميل الموظفين عند فتح القائمة
            ->getSearchResultsUsing(fn (string $search) => self::searchEmployees($search, $onlyWithoutActiveProject))
            ->getOptionLabelUsing(fn ($value) => self::getEmployeeLabel($value))
            ->required();
    }

    protected static function searchEmployees(string $search, bool $onlyWithoutActiveProject = false)
    {
        $query = Employee::active()->where(function ($query) use ($search) {
            $query->where('national_id', 'like', "%{$search}%") // البحث برقم الهوية
                ->orWhere('first_name', 'like', "%{$search}%")  // البحث بالاسم الأول
                ->orWhere('family_name', 'like', "%{$search}%") // البحث باسم العائلة
                ->orWhere('id', 'like', "%{$search}%"); // البحث بمعرف الموظف
        });

        if ($onlyWithoutActiveProject) {
            $query->whereDoesntHave('employeeProjectRecords', function ($query) {
                $query->where('status', 1); // تأكد من أن الموظف ليس لديه سجل نشط
            });
        }

        return $query->limit(50)
            ->get()
            ->mapWithKeys(fn ($employee) => [
                $employee->id => "{$employee->first_name} {$employee->family_name} - {$employee->national_id} ({$employee->id})",
            ]);
    }

    protected static function getEmployeeLabel($value)
    {
        $employee = Employee::active()->find($value);

        return $employee
            ? "{$employee->first_name} {$employee->family_name} - {$employee->national_id} ({$employee->id})"
            : null;
    }

    protected static function getDefaultOptions(bool $onlyWithoutActiveProject = false)
    {
        $query = Employee::active()->limit(50);

        if ($onlyWithoutActiveProject) {
            $query->whereDoesntHave('projectRecords', function ($query) {
                $query->where('status', 1);
            });
        }

        return $query->get()
            ->mapWithKeys(fn ($employee) => [
                $employee->id => "{$employee->name} - {$employee->national_id} ({$employee->id})",
            ])
            ->toArray();
    }
}
