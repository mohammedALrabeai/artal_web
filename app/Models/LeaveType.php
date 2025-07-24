<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_paid',
    ];

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }
}
