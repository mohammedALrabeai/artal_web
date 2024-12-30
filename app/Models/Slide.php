<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Slide extends Model
{
    use HasFactory;

    /**
     * الحقول القابلة للتعبئة.
     */
    protected $fillable = [
        'title',
        'description',
        'image_url',
        'is_active',
    ];

    /**
     * الحقول التي يتم تحويلها لأنواع مختلفة.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

     // الحصول على رابط كامل للصورة
     public function getImageUrlAttribute($value)
     {
        //  return $value ? asset('storage/' . $value) : null;
         return $value ? Storage::disk('s3')->url($value) : null;
     }
}
