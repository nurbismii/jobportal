<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateAssessmentLinkPinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return ['pin' => ['required', 'string', 'min:6', 'max:32', 'confirmed']];
    }

    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson()) parent::failedValidation($validator);
        throw new HttpResponseException(back()->withErrors($validator));
    }
}
