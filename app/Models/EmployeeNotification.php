<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeNotification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'type',
        'title',
        'message',
        'attachment',
        'sent_via_whatsapp',
        'is_read',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
