<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\DB;

class EmployeeSelectV2 extends Select
{
    public static function make(string $name = 'employee_id', bool $onlyWithoutActiveProject = false): static
    {
        return parent::make($name)
            ->label(__('Employee'))
            ->preload(false)
            ->searchable()
            ->placeholder(__('Search for an employee...'))
            ->options([])
            ->getSearchResultsUsing(fn (string $search) => self::searchEmployees($search, $onlyWithoutActiveProject))
            ->getOptionLabelUsing(fn ($value) => self::getEmployeeLabel($value))
            ->required();
    }

    protected static function searchEmployees(string $search, bool $onlyWithoutActiveProject = false)
    {
        $words = preg_split('/\s+/', trim($search));

        $query = DB::table('employees')
            ->select([
                'id',
                'first_name',
                'father_name',
                'grandfather_name',
                'family_name',
                'national_id',
            ])
            ->where('status', true)
            ->where(function ($q) use ($words, $search) {
                if (count($words) === 2) {
                    // مثال: محمد الربيعي => بحث مشترك في الاسم الأول واسم العائلة
                    $q->where('first_name', 'like', "{$words[0]}%")
                        ->where('family_name', 'like', "{$words[1]}%");
                } else {
                    $q->where('first_name', 'like', "{$search}%")
                        ->orWhere('family_name', 'like', "{$search}%")
                        ->orWhere('national_id', 'like', "{$search}%")
                        ->orWhere('id', 'like', "{$search}%");
                }
            });

        if ($onlyWithoutActiveProject) {
            $query->whereNotExists(function ($subquery) {
                $subquery->select(DB::raw(1))
                    ->from('employee_project_records')
                    ->whereRaw('employee_project_records.employee_id = employees.id')
                    ->where('status', 1);
            });
        }

        return $query->limit(50)
            ->get()
            ->mapWithKeys(fn ($employee) => [
                $employee->id => self::formatName($employee),
            ]);
    }

    protected static function getEmployeeLabel($value): ?string
    {
        return cache()->remember("employee_label_{$value}", 60, function () use ($value) {
            $employee = DB::table('employees')
                ->select([
                    'id',
                    'first_name',
                    'father_name',
                    'grandfather_name',
                    'family_name',
                    'national_id',
                ])
                ->where('status', true)
                ->where('id', $value)
                ->first();

            return $employee ? self::formatName($employee) : null;
        });
    }

    protected static function formatName($employee): string
    {
        $fullName = implode(' ', array_filter([
            $employee->first_name,
            $employee->father_name,
            $employee->grandfather_name,
            $employee->family_name,
        ]));

        return "{$fullName} - {$employee->national_id} ({$employee->id})";
    }
}
