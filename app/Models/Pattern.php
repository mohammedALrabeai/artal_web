<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pattern extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'working_days',
        'off_days',
        'hours_cat',
    ];

    // علاقة مع المناطق
    public function zones()
    {
        return $this->hasMany(Zone::class);
    }
}
