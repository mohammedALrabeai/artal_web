<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    use HasFactory;

    /**
     * الحقول القابلة للتعبئة.
     */
    protected $fillable = [
        'policy_name',
        'policy_type',
        'description',
        'conditions',
        'request_type_id'
    ];

    /**
     * تحويل الحقول.
     */
    protected $casts = [
        'conditions' => 'array', // تحويل الشروط إلى JSON تلقائيًا
    ];

    /**
     * العلاقة مع الطلبات (اختياري إذا كنت تريد ربط الطلب بالسياسة).
     */
    public function requests()
    {
        return $this->hasMany(Request::class, 'type', 'policy_type');
    }

    public function requestType()
{
    return $this->belongsTo(RequestType::class);
}

}
