<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Collection;

class Employee extends Model 
{
   
    use HasFactory,Notifiable, LogsActivity;
    

    protected $fillable = [
        'id',
        'first_name',
        'father_name',
        'grandfather_name',
        'family_name',
        'birth_date',
        'national_id',
        'national_id_expiry',
        'nationality',
        'bank_account',
        'blood_type',
        'contract_start',
        'contract_end',
        'actual_start',
        'basic_salary',
        'living_allowance',
        'other_allowances',
        'job_status',
        'health_insurance_company',
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
        'api_token',
        'leave_balance',
        'out_of_zone',
        'insurance_company_id',
        'parent_insurance', 'insurance_company_name',
        'commercial_record_id',
        'job_title',
        'bank_name',
        'insurance_type',
        'insurance_number',
        'insurance_start_date',
        'insurance_end_date',
        'contract_type'
    ];
    protected $casts = [
        'out_of_zone' => 'boolean',
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


public function projectRecords()
{
    return $this->hasMany(EmployeeProjectRecord::class, 'employee_id');
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



public function exclusions()
{
    return $this->hasMany(Exclusion::class);
}

public function fullAddress()
{
    return $this->region . ' - ' . $this->city . ' - ' . $this->street . ' - ' . $this->building_number . ' - ' . $this->apartment_number;
}

public function insuranceCompany()
{
    return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id');
}


public function leaveBalances()
{
    return $this->hasMany(LeaveBalance::class);
}
public function leaves()
{
    return $this->hasMany(Leave::class, 'employee_id');
}


public function currentProjectRecord()
{
    return $this->hasOne(EmployeeProjectRecord::class, 'employee_id')
        ->where(function ($query) {
            $query->whereNull('end_date') // إذا كان تاريخ النهاية غير محدد
                ->orWhere('end_date', '>=', now()); // أو يقع في المستقبل
        })
        ->latest('start_date'); // جلب أحدث سجل بناءً على تاريخ البداية
}

public function currentZone()
{
    return $this->hasOneThrough(
        Zone::class, 
        EmployeeProjectRecord::class, 
        'employee_id', // المفتاح الأجنبي في جدول EmployeeProjectRecord
        'id', // المفتاح الأساسي في جدول Zone
        'id', // المفتاح الأساسي في جدول Employee
        'zone_id' // المفتاح الأجنبي في جدول Zone
    )
    ->where(function ($query) {
        $query->whereNull('end_date') // إذا كان تاريخ النهاية غير محدد
            ->orWhere('end_date', '>=', now()); // أو يقع في المستقبل
    })
    ->latest('start_date'); // جلب أحدث سجل بناءً على تاريخ البداية
}


public function commercialRecord()
{
    return $this->belongsTo(CommercialRecord::class, 'commercial_record_id');
}

public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'model');
    }

  
    public function requests()
    {
        return $this->hasMany(Request::class);
    }
    public function requestAttachments(): HasManyThrough
    {
        return $this->hasManyThrough(Attachment::class, Request::class);
    }

    public function allAttachments(): Collection
    {
        return $this->attachments->merge($this->requestAttachments);
    }


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
