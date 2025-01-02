<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type',
        'balance',
        'accrued_per_month',
        'used_balance',
        'last_updated',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
