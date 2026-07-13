<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssessmentLinkRequest extends FormRequest
{
    public function authorize()
    {
        return optional($this->user())->role === 'admin';
    }

    public function rules()
    {
        return [
            'assessment_type' => ['required', Rule::in(['kesehatan', 'lapangan'])],
            'pin' => ['required', 'string', 'min:6', 'max:32'],
            'selected_ids' => ['required', 'array', 'min:1'],
            'selected_ids.*' => ['required', 'integer', 'distinct', 'exists:lamaran,id'],
            'fields' => ['nullable', 'array', 'max:20'],
            'fields.*' => ['required', 'array'],
            'fields.*.id' => ['required', 'string', 'max:100'],
            'fields.*.label' => ['required', 'string', 'max:100'],
            'fields.*.type' => ['required', Rule::in(['select', 'number', 'text'])],
            'fields.*.required' => ['nullable', 'boolean'],
            'fields.*.options' => ['nullable', 'array', 'max:20'],
            'fields.*.options.*' => ['required', 'string', 'max:100'],
        ];
    }
}
