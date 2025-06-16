<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Attendance */
class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'date'       => Carbon::parse($this->date)->toDateString(),
            'status'     => $this->status,
            'check_in'   => $this->check_in,
            'check_out'  => $this->check_out,
            'is_late'    => $this->is_late,
            'zone_id'    => $this->zone_id,
            'zone_name'  => $this->zone?->name,
            'shift_id'   => $this->shift_id,
            'shift_name' => $this->shift?->name,
        ];
    }
}
