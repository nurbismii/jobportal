<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PkwtContractSettingRequest extends FormRequest
{
    public function authorize()
    {
        return optional($this->user())->role === 'admin';
    }

    public function rules()
    {
        return [
            'duration_value' => ['required', 'integer', 'min:1', 'max:120'],
            'duration_unit' => ['required', Rule::in(['day', 'week', 'month', 'year'])],
            'default_signing_method' => ['required', Rule::in(['electronic', 'manual'])],
        ];
    }
}
