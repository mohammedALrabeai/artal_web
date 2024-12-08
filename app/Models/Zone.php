<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

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


}
