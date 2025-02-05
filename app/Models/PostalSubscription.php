<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostalSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'commercial_record_id',
        'subscription_number',
        'start_date',
        'expiry_date',
        'notes',
        'mobile_number', 'expiry_date_hijri',
    ];

    // علاقة مع السجلات التجارية
    public function commercialRecord()
    {
        return $this->belongsTo(CommercialRecord::class, 'commercial_record_id');
    }

    public function recordMedia()
    {
        return $this->morphMany(RecordMedia::class, 'recordable');
    }
}
