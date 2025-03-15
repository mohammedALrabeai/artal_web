<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'employee_id',
        'assigned_date',
        'expected_return_date',
        'returned_date',
        'condition_at_assignment',
        'condition_at_return',
        'notes',
    ];

    // علاقة تربط عملية التعيين بالأصل
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    // علاقة تربط عملية التعيين بالموظف
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
