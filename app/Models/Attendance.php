<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;



class Attendance extends Model
{
    use HasFactory, LogsActivity;

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



    //   // تفعيل تسجيل التغييرات
    //   protected static $logAttributes = ['*'];
    //   protected static $logOnlyDirty = true;
    //   protected static $submitEmptyLogs = false;
    //   protected static $logName = 'attendance';
  
      public function getDescriptionForEvent(string $eventName): string
      {
          return "Attendance record has been {$eventName}";
      }

      public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logAll() // تسجيل جميع الحقول
        ->logOnlyDirty() // تسجيل الحقول التي تغيرت فقط
        ->dontSubmitEmptyLogs(); // تجاهل التعديلات الفارغة
}
}
