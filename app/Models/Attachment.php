<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Attachment extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'employee_id',
        'added_by',
        'type',
        'expiry_date',
        'notes',
        'title',
        'request_id',
        'model_type',
        'model_id',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')->useDisk('s3');
    }

    public function getFileUrlAttribute()
    {
        return $this->getFirstMediaUrl('attachments');
    }

    public static function boot()
    {
        parent::boot();
        static::creating(function ($record) {
            $record->added_by = Auth::id() ?? 1; // تعيين المستخدم الحالي
        });
        static::deleting(function ($attachment) {
            if ($attachment->hasMedia('attachments')) {
                $attachment->clearMediaCollection('attachments'); // ✅ حذف الملفات من `Spatie Media Library`
            }
        });
    }

    public function exclusion()
    {
        return $this->belongsTo(Exclusion::class);
    }

    // ✅ عرض الملف حسب نوعه
    public function getContentDisplayAttribute()
    {
        $file = $this->getFirstMedia('attachments');

        if (! $file) {
            return '<span class="text-gray-500">'.__('No File').'</span>';
        }

        switch ($file->mime_type) {
            case 'image/png':
            case 'image/jpeg':
            case 'image/gif':
                return "<a href='{$file->getTemporaryUrl(now()->addMinutes(30))}' target='_blank'>
                            <img src='{$file->getTemporaryUrl(now()->addMinutes(30))}' width='50' style='border-radius: 5px;' />
                        </a>";

            case 'video/mp4':
            case 'video/mpeg':
                return "<video width='320' height='240' controls>
                            <source src='{$file->getTemporaryUrl(now()->addMinutes(30))}' type='video/mp4'>
                            ".__('Your browser does not support the video tag.').'
                        </video>';

            case 'application/pdf':
                return "<a href='{$file->getTemporaryUrl(now()->addMinutes(30))}' target='_blank'>
                            📄 ".__('View PDF').'
                        </a>';

            default:
                return "<a href='{$file->getTemporaryUrl(now()->addMinutes(30))}' target='_blank'>
                            📂 ".__('Download File').'
                        </a>';
        }
    }
}
