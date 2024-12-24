<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;



class Employee extends Model
{
    use HasFactory,Notifiable, LogsActivity;
    

    protected $fillable = [
        'first_name',
        'father_name',
        'grandfather_name',
        'family_name',
        'birth_date',
        'national_id',
        'national_id_expiry',
        'nationality',
        'bank_account',
        'sponsor_company',
        'blood_type',
        'contract_start',
        'contract_end',
        'actual_start',
        'basic_salary',
        'living_allowance',
        'other_allowances',
        'job_status',
        'health_insurance_status',
        'health_insurance_company',
        'vacation_balance',
        'social_security',
        'social_security_code',
        'qualification',
        'specialization',
        'mobile_number',
        'phone_number',
        'region',
        'city',
        'street',
        'building_number',
        'apartment_number',
        'postal_code',
        'facebook',
        'twitter',
        'linkedin',
        'email',
        'password',
        'added_by',
        'status',
        'onesignal_player_id',
        'remember_token',
        'api_token'
    ];

    // علاقة مع المستخدم الذي أضاف الموظف
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
    public function zones()
{
    return $this->hasMany(EmployeeZone::class);
}

public function projects()
{
    return $this->belongsToMany(Project::class, 'employee_project_records')
        ->withPivot('start_date', 'end_date', 'status');
}

// public function projectRecords()
// {
//     return $this->hasMany(EmployeeProjectRecord::class);
// }
public function projectRecords()
{
    return $this->hasMany(EmployeeProjectRecord::class, 'employee_id');
}

public function attachments(){
    return $this->hasMany(Attachment::class);
}

public function attendances()
{
    return $this->hasMany(Attendance::class);
}

public function devices()
{
    return $this->hasMany(EmployeeDevice::class);
}


public function loans()
{
    return $this->hasMany(Loan::class);
}

public function name()
{
    return $this->first_name . ' ' . $this->father_name . ' ' . $this->family_name;
}
public function resignations()
{
    return $this->hasMany(Resignation::class);
}
public function fullAddress()
{
    return $this->region . ' - ' . $this->city . ' - ' . $this->street . ' - ' . $this->building_number . ' - ' . $this->apartment_number;
}














// // تفعيل تسجيل التغييرات
// protected static $logAttributes = ['*']; // تسجيل جميع الحقول
// protected static $logOnlyDirty = true; // تسجيل الحقول التي تغيرت فقط
// protected static $submitEmptyLogs = false; // عدم تسجيل التعديلات الفارغة
// protected static $logName = 'employee'; // اسم الـ log

public function getDescriptionForEvent(string $eventName): string
{
    return "Employee record has been {$eventName}";
}

public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logAll() // تسجيل جميع الحقول
        ->logOnlyDirty() // تسجيل الحقول التي تغيرت فقط
        ->dontSubmitEmptyLogs(); // تجاهل التعديلات الفارغة
}

}
