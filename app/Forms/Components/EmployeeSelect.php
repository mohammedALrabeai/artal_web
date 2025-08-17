<?php

namespace App\Forms\Components;

use App\Models\Employee;
use Filament\Forms\Components\Select;

class EmployeeSelect extends Select
{
    /**
     * @param  string   $name                      اسم الحقل (افتراضي employee_id)
     * @param  bool     $onlyWithoutActiveProject  استبعاد من لديهم سجل إسناد نشط
     * @param  int|null $projectId                 عند تمريره يُعاد فقط موظفو هذا المشروع
     */
    public static function make(
        string $name = 'employee_id',
        bool $onlyWithoutActiveProject = false,
        ?int $projectId = null
    ): static {
        return parent::make($name)
            ->label(__('Employee'))
            ->preload()
            ->searchable()
            ->placeholder(__('Search for an employee...'))
            ->options(fn () => self::getDefaultOptions($onlyWithoutActiveProject, $projectId))
            ->getSearchResultsUsing(fn (string $search) => self::searchEmployees($search, $onlyWithoutActiveProject, $projectId))
            ->getOptionLabelUsing(fn ($value) => self::getEmployeeLabel($value))
            ->required();
    }

    /**
     * نتائج البحث الديناميكية
     */
    protected static function searchEmployees(string $search, bool $onlyWithoutActiveProject = false, ?int $projectId = null)
    {
        $query = Employee::active()
            ->where(function ($q) use ($search) {
                $q->where('national_id', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('family_name', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });

        // استبعاد من لديهم إسناد نشط
        if ($onlyWithoutActiveProject) {
            $query->whereDoesntHave('projectRecords', function ($q) {
                $q->where('status', 1);
            });
        }

        // التصفية حسب مشروع معيّن
        if (!is_null($projectId)) {
            $query->whereHas('projectRecords', function ($q) use ($projectId) {
                $q->where('project_id', $projectId)
                // إن أردت قصرها على السجلات النشطة فقط أضف:
                ->where('status', 1);
            });
        }

        return $query
            ->orderBy('first_name')
            ->limit(50)
            ->get(['id', 'first_name', 'family_name', 'national_id'])
            ->mapWithKeys(fn ($employee) => [
                $employee->id => "{$employee->first_name} {$employee->family_name} - {$employee->national_id} ({$employee->id})",
            ]);
    }

    /**
     * تسمية الخيار عند وجود قيمة محفوظة
     */
    protected static function getEmployeeLabel($value)
    {
        $employee = Employee::active()
            ->select(['id', 'first_name', 'family_name', 'national_id'])
            ->find($value);

        return $employee
            ? "{$employee->first_name} {$employee->family_name} - {$employee->national_id} ({$employee->id})"
            : null;
    }

    /**
     * خيارات التحميل الأولي (preload)
     */
    protected static function getDefaultOptions(bool $onlyWithoutActiveProject = false, ?int $projectId = null): array
    {
        $query = Employee::active();

        if ($onlyWithoutActiveProject) {
            $query->whereDoesntHave('projectRecords', function ($q) {
                $q->where('status', 1);
            });
        }

        if (!is_null($projectId)) {
            $query->whereHas('projectRecords', function ($q) use ($projectId) {
                $q->where('project_id', $projectId);
                // إن رغبت: ->where('status', 1);
            });
        }

        return $query
            ->orderBy('first_name')
            ->limit(50)
            ->get(['id', 'first_name', 'family_name', 'national_id'])
            ->mapWithKeys(fn ($employee) => [
                $employee->id => "{$employee->first_name} {$employee->family_name} - {$employee->national_id} ({$employee->id})",
            ])
            ->toArray();
    }
}
