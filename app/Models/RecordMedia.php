<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class RecordMedia extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'title',
        'notes',
        'expiry_date',
        'added_by',
        'recordable_id',
        'recordable_type',
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($record) {
            $record->added_by = Auth::id() ?? 1; // تعيين المستخدم الحالي
        });
        static::deleting(function ($recordMedia) {
            if ($recordMedia->hasMedia('record_media')) {
                $recordMedia->clearMediaCollection('record_media'); // ✅ حذف الملفات من `Spatie Media Library`
            }
        });
    }

    public function recordable(): MorphTo
    {
        return $this->morphTo();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('record_media')->useDisk('s3'); // ✅ استخدام S3 كمخزن أساسي
    }
}
