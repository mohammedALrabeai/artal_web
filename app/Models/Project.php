<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'area_id',
        'start_date',
        'end_date',
        'emp_no',
        'status',
    ];

    // علاقة مع المنطقة
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    // علاقة مع المواقع
    public function zones()
    {
        return $this->hasMany(Zone::class);
    }

    // علاقة مع الموظفين
    // public function employees()
    // {
    //     return $this->belongsToMany(Employee::class, 'employee_project_records')
    //         ->withPivot('start_date', 'end_date', 'status');
    // }
    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_project_records')
            ->withPivot('start_date', 'end_date', 'zone_id', 'shift_id', 'status');
    }

    public function activeZones()
    {
        return $this->hasMany(Zone::class)->where('status', 1);
    }

    public function employeeProjectRecords()
{
    return $this->hasMany(\App\Models\EmployeeProjectRecord::class, 'project_id');
}

}
