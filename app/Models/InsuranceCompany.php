<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceCompany extends Model
{
    use HasFactory;

    // تحديد الأعمدة القابلة للتعبئة
    protected $fillable = [
        'name',
        'activation_date',
        'expiration_date',
        'policy_number',
        'branch',
        'is_active',
    ];

    // العلاقة مع الموظفين
    public function employees()
    {
        return $this->hasMany(Employee::class, 'insurance_company_id');
    }

    public function recordMedia()
    {
        return $this->morphMany(RecordMedia::class, 'recordable');
    }
}
