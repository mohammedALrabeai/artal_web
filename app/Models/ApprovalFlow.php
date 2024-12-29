<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalFlow extends Model
{
    use HasFactory;

    /**
     * الحقول القابلة للتعبئة.
     */
    protected $fillable = [
        'request_type',
        'approval_level',
        'approver_role',
        'conditions',
    ];

    /**
     * تحويل الحقول.
     */
    protected $casts = [
        'conditions' => 'array', // تحويل الشروط إلى JSON تلقائيًا
    ];

    /**
     * العلاقة مع الطلبات.
     */
    public function requests()
    {
        return $this->hasMany(Request::class, 'type', 'request_type');
    }

        /**
     * دمج الشروط من سلسلة الموافقات والسياسات.
     */
    public function combinedConditions($policyConditions = [])
    {
        $flowConditions = is_array($this->conditions) ? $this->conditions : json_decode($this->conditions, true);
        return array_merge($policyConditions, $flowConditions);
    }
}
