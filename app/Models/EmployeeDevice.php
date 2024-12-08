<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'device_id',
        'status',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
