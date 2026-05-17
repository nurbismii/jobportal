<?php

namespace App\Http\Requests\Api\Vhire;

use Illuminate\Foundation\Http\FormRequest;

class HrisCandidateActivatedRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'vhire_candidate_id' => ['nullable', 'string', 'max:100'],
            'candidate_code' => ['nullable', 'string', 'max:100'],
            'no_ktp' => ['nullable', 'digits:16'],
            'employee_nik' => ['required', 'string', 'max:50'],
            'activated_as_employee_at' => ['nullable', 'date'],
        ];
    }
}
