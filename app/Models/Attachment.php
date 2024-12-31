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

    public function getContentUrlAttribute()
{
    return $this->type !== 'text' && $this->type !== 'link'
        ? Storage::disk('s3')->url($this->content)
        : $this->content;
}

public function getContentDisplayAttribute()
{
    switch ($this->type) {
        case 'text':
            return $this->content;
        case 'link':
            return "<a href='{$this->content}' target='_blank'>{$this->content}</a>";
        case 'image':
            return "<img src='".Storage::disk('s3')->url($this->file_url)."' width='50' style='border-radius: 5px;' />";
        case 'video':
        case 'file':
            return "<a href='".Storage::disk('s3')->url($this->file_url)."' target='_blank'>".__('Download File')."</a>";
        default:
            return '';
    }
}
public function getFileUrlAttribute($value)
{
    return $value ? Storage::disk('s3')->url($value) : null;
}


}
