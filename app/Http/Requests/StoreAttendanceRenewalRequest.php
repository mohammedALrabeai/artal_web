<?php

// app/Http/Requests/StoreAttendanceRenewalRequest.php
// app/Http/Requests/StoreAttendanceRenewalRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Attendance;

class StoreAttendanceRenewalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // عدّل الصلاحيات حسب نظامك
    }

    protected function prepareForValidation(): void
    {
        // يدعم: رقم خام أو Model عبر Route Model Binding
        $routeAttendance = $this->route('attendance') ?? $this->route('attendance_id') ?? $this->route('id');

        if ($routeAttendance instanceof Attendance) {
            $routeAttendance = $routeAttendance->id;
        }

        if (!$this->filled('attendance_id') && $routeAttendance) {
            $this->merge(['attendance_id' => (int) $routeAttendance]);
        }
    }

    public function rules(): array
    {
        return [
            'attendance_id' => ['required','integer','exists:attendances,id'],
            'kind'          => ['nullable','string','max:20'],
            'status'        => ['nullable','string','max:20'],
            'payload'       => ['nullable','array'],
        ];
    }

    public function messages(): array
    {
        return [
            'attendance_id.required' => 'attendance_id مطلوب.',
            'attendance_id.exists'   => 'attendance_id غير موجود.',
        ];
    }
}
