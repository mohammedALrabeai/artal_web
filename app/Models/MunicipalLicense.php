<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MunicipalLicense extends Model
{
    use HasFactory;

    protected $fillable = [
        'commercial_record_id',
        'license_number',
        'expiry_date_hijri',
        'expiry_date_gregorian',
        'vat',
        'notes',
    ];

    // علاقة مع السجلات التجارية
    public function commercialRecord()
    {
        return $this->belongsTo(CommercialRecord::class, 'commercial_record_id');
    }
}
