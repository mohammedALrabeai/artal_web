<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShiftSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
        'slot_number',
    ];

    // 🔁 كل مكان تابع لوردية واحدة
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }




    // 🔁 المكان قد يكون مرتبطًا بسجلات متعددة عبر الزمن
    public function employeeProjectRecords()
    {
        return $this->hasMany(EmployeeProjectRecord::class);
    }
}
