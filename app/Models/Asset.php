<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_name',
        'description',
        'serial_number',
        'purchase_date',
        'value',
        'condition',
        'status',
    ];

    // علاقة تربط الأصل بكافة عمليات التعيين الخاصة به
    public function assignments()
    {
        return $this->hasMany(AssetAssignment::class);
    }
}
