<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveSubstitute extends Model
{
    protected $fillable = [
        'leave_id',
        'substitute_employee_id',
        'start_date',
        'end_date',
    ];

    public function leave()
    {
        return $this->belongsTo(Leave::class);
    }

    public function substitute()
    {
        return $this->belongsTo(Employee::class, 'substitute_employee_id');
    }
}
