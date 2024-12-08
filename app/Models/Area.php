<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    // علاقة مع المشاريع
    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
