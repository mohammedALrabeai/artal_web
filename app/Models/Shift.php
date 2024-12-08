<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'zone_id',
        'type',
        'morning_start',
        'morning_end',
        'evening_start',
        'evening_end',
        'early_entry_time',
        'last_entry_time',
        'early_exit_time',
        'last_time_out',
        'start_date',
        'emp_no',
        'status',
    ];

    // علاقة مع المواقع
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

        // تخزين القيم بالدقائق كوقت
        public function setEarlyEntryTimeAttribute($value)
        {
            $this->attributes['early_entry_time'] = gmdate('H:i:s', $value * 60);
        }
    
        public function setLastEntryTimeAttribute($value)
        {
            $this->attributes['last_entry_time'] = gmdate('H:i:s', $value * 60);
        }
    
        public function setEarlyExitTimeAttribute($value)
        {
            $this->attributes['early_exit_time'] = gmdate('H:i:s', $value * 60);
        }
    
        public function setLastTimeOutAttribute($value)
        {
            $this->attributes['last_time_out'] = gmdate('H:i:s', $value * 60);
        }
    
        public function getEarlyEntryTimeAttribute($value)
        {
            if ($value) {
                $parts = explode(':', $value); // فصل الساعات والدقائق والثواني
                return ($parts[0] * 60) + $parts[1]; // حساب الدقائق (الساعات × 60) + الدقائق
            }
            return null;
        }
        
        public function getLastEntryTimeAttribute($value)
        {
            if ($value) {
                $parts = explode(':', $value);
                return ($parts[0] * 60) + $parts[1];
            }
            return null;
        }
        
        public function getEarlyExitTimeAttribute($value)
        {
            if ($value) {
                $parts = explode(':', $value);
                return ($parts[0] * 60) + $parts[1];
            }
            return null;
        }
        
        public function getLastTimeOutAttribute($value)
        {
            if ($value) {
                $parts = explode(':', $value);
                return ($parts[0] * 60) + $parts[1];
            }
            return null;
        }
        
}
