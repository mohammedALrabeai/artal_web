<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'zone_id',
        'start_date',
        'end_date',
        'status',
        'added_by',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
