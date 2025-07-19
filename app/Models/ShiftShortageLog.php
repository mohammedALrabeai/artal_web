<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftShortageLog extends Model
{
    protected $fillable = [
        'shift_id',
        'date',
        'is_shortage',
        'notes',
    ];

    protected $casts = [
        'is_shortage' => 'boolean',
        'date' => 'date',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
