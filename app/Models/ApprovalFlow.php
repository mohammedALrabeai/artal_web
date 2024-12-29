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
}
