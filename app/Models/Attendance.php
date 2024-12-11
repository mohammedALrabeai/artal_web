<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'zone_id',
        'shift_id',

        'date',
        'ismorning',
        'check_in',
        'check_in_datetime',
        'check_out',
        'check_out_datetime',
        'status',
        'work_hours', 'notes', 'is_late'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
