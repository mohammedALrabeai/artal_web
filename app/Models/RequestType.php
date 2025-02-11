<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestType extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'is_active', // ✅ إضافة العمود الجديد

        
    ];

    public function policies()
{
    return $this->hasMany(Policy::class);
}

}
