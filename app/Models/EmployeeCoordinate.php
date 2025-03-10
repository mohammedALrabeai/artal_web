<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeCoordinate extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'latitude',
        'longitude',
        'timestamp',
        'status',
        'shift_id',
        'zone_id',
        'distance',
    ];

    protected $casts = [
        'latitude' => 'double',
        'longitude' => 'double',
        // 'timestamp' => 'datetime',
    ];

    protected $guarded = [];

    protected static function booted()
    {
        static::created(function ($coordinate) {
            // تحديث last_active في جدول الموظفين
            $coordinate->employee->update([
                'last_active' => $coordinate->created_at
            ]);
        });
    }

    // العلاقات
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
}
