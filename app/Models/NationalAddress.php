<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NationalAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'commercial_record_id',
        'expiry_date',
        'notes',
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
