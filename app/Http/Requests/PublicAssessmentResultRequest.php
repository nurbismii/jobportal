<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicAssessmentResultRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'values' => ['required', 'array'],
            'petugas_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
