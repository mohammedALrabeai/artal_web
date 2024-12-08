<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'added_by',
        'type',
        'content',
        'expiry_date',
        'notes',
        'title', 
    ];

    // العلاقة مع الموظف
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // العلاقة مع المستخدم الذي أضاف الوثيقة
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
