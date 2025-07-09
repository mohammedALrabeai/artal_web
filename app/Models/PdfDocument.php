<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage; // أضف هذا السطر


class PdfDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'file_path',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function textFields(): HasMany
    {
        return $this->hasMany(PdfTextField::class);
    }

    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    //    public function getFileUrlAttribute(): string
    // {
    //     // تأكد من أن 'public' هو القرص الذي ترفع عليه الملفات
    //     return Storage::disk('public')->url($this->file_path);
    // }
}

