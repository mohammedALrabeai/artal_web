<?php

// app/Models/Asset.php
namespace App\Models;

use App\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_name',
        'description',
        'serial_number',
        'inventory_code',
        'purchase_date',
        'value',
        'condition',
        'status',
    ];



    protected $casts = [
        'status' => AssetStatus::class,
    ];

    public function assignments()
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function openAssignment()
    {
        return $this->hasOne(AssetAssignment::class)->whereNull('returned_date');
    }

    // أصل متاح: لا يوجد له تعيين مفتوح
 



      public function scopeAvailable($query)
    {
        return $query
            ->where('status', AssetStatus::AVAILABLE->value)
            ->whereDoesntHave('assignments', fn ($q) => $q->whereNull('returned_date'));
    }
}
