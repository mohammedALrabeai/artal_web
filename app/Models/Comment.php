<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'related_table',
        'related_id',
        'comment',
    ];

    /**
     * علاقة ديناميكية مع الجدول المرتبط
     */
    public function related()
    {
        return $this->morphTo();
    }
}
