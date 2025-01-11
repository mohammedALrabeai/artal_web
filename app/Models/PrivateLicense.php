<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivateLicense extends Model
{
    use HasFactory;

    protected $fillable = [
        'commercial_record_id',
        'license_name',
        'license_number',
        'issue_date',
        'expiry_date',
        'description',
        'website',
        'platform_username',
        'platform_password',
        'platform_user_id',
        'expiry_date_hijri',
    ];

    // علاقة مع السجلات التجارية
    public function commercialRecord()
    {
        return $this->belongsTo(CommercialRecord::class, 'commercial_record_id');
    }
}
