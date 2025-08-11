<?php

// app/Models/AssetAssignment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AssetAssignment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'asset_id',
        'employee_id',
        'assigned_date',
        'expected_return_date',
        'returned_date',
        'condition_at_assignment',
        'condition_at_return',
        'notes',
        'assigned_by_user_id',
        'returned_by_user_id',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'expected_return_date' => 'date',
        'returned_date' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

  

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function returnedBy()
    {
        return $this->belongsTo(User::class, 'returned_by_user_id');
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('returned_date');
    }

    public function isOpen(): bool
    {
        return is_null($this->returned_date);
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {

            // لا يسمح بتعيين أصل لديه تعيين مفتوح
            $existsOpen = self::where('asset_id', $model->asset_id)
                ->whereNull('returned_date')
                ->exists();
            if ($existsOpen) {
                throw ValidationException::withMessages([
                    'asset_id' => 'هذا الأصل لديه تعيين مفتوح بالفعل. يجب إرجاعه أولاً.',
                ]);
            }

            // لا يسمح بتسليم أصل حالته غير قابلة للتسليم
            $asset = Asset::find($model->asset_id);
            if (! $asset?->status?->isAssignable()) {
                throw ValidationException::withMessages([
                    'asset_id' => 'حالة الأصل الحالية لا تسمح بالتسليم.',
                ]);
            }

            // تواريخ منطقية (اختياري)
            if ($model->expected_return_date && $model->assigned_date && $model->expected_return_date->lt($model->assigned_date)) {
                throw ValidationException::withMessages([
                    'expected_return_date' => 'تاريخ الاستحقاق لا يمكن أن يكون قبل تاريخ التعيين.',
                ]);
            }

            // تعيين المُسلِّم تلقائيًا
            if (Auth::check() && empty($model->assigned_by_user_id)) {
                $model->assigned_by_user_id = Auth::id();
            }
        });

        static::updating(function (self $model) {
            // عند الإرجاع: ضع returned_by
            if ($model->isDirty('returned_date') && $model->returned_date && Auth::check() && empty($model->returned_by_user_id)) {
                $model->returned_by_user_id = Auth::id();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('asset_assignments')->logFillable()->logOnlyDirty();
    }
   
}
