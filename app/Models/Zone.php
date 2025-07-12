<?php

namespace App\Models;

use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Zone extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'name',
        'start_date',
        'pattern_id',
        'lat',
        'longg',
        'area',
        'emp_no',
        'status',
        'project_id',
    ];

    protected $casts = [
        'lat' => 'float',
        'longg' => 'float', // التأكد من استخدام الاسم الجديد
        'area' => 'integer', // تحديد النوع كعدد صحيح
    ];

    // علاقة مع الأنماط
    public function pattern()
    {
        return $this->belongsTo(Pattern::class);
    }

    // علاقة مع الورديات
    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    // سكوب الورديات النشطة 
    public function activeShifts()
    {
        return $this->shifts()->where('status', true);
    }

    // public function employees()
    // {
    //     return $this->hasMany(Employee::class, 'zone_id');
    // }
    public function employees()
    {
        return $this->hasMany(EmployeeZone::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        if ($eventName === 'updated') {
            $notificationService = new NotificationService;
            $editedBy = auth()->user()->name;
            $zone = $this;

            // مقارنة الحقول القديمة بالجديدة
            $changes = $zone->getChanges();
            $original = $zone->getOriginal();
            $ignoredFields = ['updated_at', 'created_at'];
            $changeDetails = '';

            foreach ($changes as $field => $newValue) {
                if (! in_array($field, $ignoredFields) && isset($original[$field]) && $original[$field] !== $newValue) {
                    $changeDetails .= ucfirst(str_replace('_', ' ', $field))
                        .": \"{$original[$field]}\" → \"{$newValue}\"\n";
                }
            }

            $message = "تم تعديل بيانات الموقع بنجاح\n\n";
            $message .= "الموقع: {$zone->name}\n";
            $message .= "تم التعديل بواسطة: {$editedBy}\n\n";
            $message .= "تفاصيل التعديل:\n";
            $message .= ! empty($changeDetails) ? $changeDetails : "⚠️ لم يتم الكشف عن تغييرات كبيرة.\n";

            $notificationService->sendNotification(
                ['manager', 'general_manager', 'hr'],
                'تعديل بيانات الموقع',
                $message,
                [
                    $notificationService->createAction('عرض بيانات الموقع', "/admin/zones/{$zone->id}", 'heroicon-s-eye'),
                    $notificationService->createAction('قائمة المواقع', '/admin/zones', 'heroicon-s-users'),
                ]
            );
        }

        return "Zone record has been {$eventName}";
    }

    public function getMapUrlAttribute(): string
    {
        return "https://maps.google.com/?q={$this->lat},{$this->longg}";
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // تسجيل جميع الحقول
            ->logOnlyDirty() // تسجيل الحقول التي تغيرت فقط
            ->dontSubmitEmptyLogs(); // تجاهل التعديلات الفارغة
    }
}
