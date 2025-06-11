<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // عدّل حسب صلاحياتك
    }

    public function rules(): array
    {
        return [
            'code'        => ['required', 'digits_between:4,8'], // 5 خانات مثلاً
            'employee_id' => ['required', 'exists:employees,id'],
        ];
    }
}
