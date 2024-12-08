<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coverage extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'absent_employee_id',
        'zone_id',
        'date',
        'status',
        'added_by',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function absentEmployee()
    {
        return $this->belongsTo(Employee::class, 'absent_employee_id');
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
