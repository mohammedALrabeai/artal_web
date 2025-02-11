<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exclusion extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'request_id',
        'type',
        'exclusion_date',
        'reason',
        'attachment',
        'notes',
        'status', // إضافة الحقل الجديد
    ];

    // تعريف الحالات كقائمة ثابتة
    public const STATUS_PENDING = 'Pending';

    public const STATUS_APPROVED = 'Approved';

    public const STATUS_REJECTED = 'Rejected';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_APPROVED => __('Approved'),
            self::STATUS_REJECTED => __('Rejected'),
        ];
    }

    // علاقة الموظف
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    //     public function request()
    // {
    //     return $this->belongsTo(Request::class);
    // }
    public function attachments()
    {
        return $this->hasMany(\App\Models\Attachment::class, 'exclusion_id');
    }

    public function request()
    {
        return $this->hasOne(Request::class, 'exclusion_id');
    }
}
