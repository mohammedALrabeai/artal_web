<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'approver_id',
        'approver_type',
        'status',
        'notes',
        'approved_at',
    ];

    // علاقة مع الطلب
    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    // علاقة مع المستخدم الذي يوافق
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
