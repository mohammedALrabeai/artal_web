<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'added_by',
        'type',
        'content',
        'file_url',
        'expiry_date',
        'notes',
        'title', 
        'image_url',
        'video_url',

    ];

    // العلاقة مع الموظف
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // العلاقة مع المستخدم الذي أضاف الوثيقة
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

//     public function getContentUrlAttribute()
// {
//     return $this->type !== 'text' && $this->type !== 'link'
//         ? Storage::disk('s3')->url($this->content)
//         : $this->content;
// }

public function getContentUrlAttribute()
{
    switch ($this->type) {
        case 'text':
        case 'link':
            return $this->content; // النص أو الرابط يتم تخزينه في الحقل content
        case 'image':
            return $this->image_url ? Storage::disk('s3')->url($this->image_url) : null; // الصور
        case 'video':
            return $this->video_url ? Storage::disk('s3')->url($this->video_url) : null; // الفيديوهات
        case 'file':
            return $this->file_url ? Storage::disk('s3')->url($this->file_url) : null; // الملفات
        default:
            return null;
    }
}

public function getImageUrlAttribute($value)
{
   //  return $value ? asset('storage/' . $value) : null;
    return $value ? Storage::disk('s3')->url($value) : null;
}

public function getVideoUrlAttribute($value)
{
    return $value ? Storage::disk('s3')->url($value) : null;
}

public function getFileUrlAttribute($value)
{
    return $value ? Storage::disk('s3')->url($value) : null;
}

public function getContentDisplayAttribute()
{
    switch ($this->type) {
        case 'text':
            return $this->content;
        case 'link':
            return "<a href='{$this->content}' target='_blank'>{$this->content}</a>";
        case 'image':
            return "<a href='{$this->content_url}' target='_blank'><img src='{$this->content_url}' width='50' style='border-radius: 5px;' /></a>";
        case 'video':
            return "<video width='320' height='240' controls>
                        <source src='{$this->content_url}' type='video/mp4'>
                        " . __('Your browser does not support the video tag.') . "
                    </video>";
        case 'file':
            return "<a href='{$this->content_url}' target='_blank'>".__('Download File')."</a>";
        default:
            return '';
    }
}


// public function getFileUrlAttribute($value)
// {
//     return $value ? Storage::disk('s3')->url($value) : null;
// }


}
