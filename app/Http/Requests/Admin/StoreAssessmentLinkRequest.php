<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreAssessmentLinkRequest extends FormRequest
{
    protected $dontFlash = ['pin'];

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
            'eligibility_field_id' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->redirector->to($this->getRedirectUrl())
                ->withErrors($validator, $this->errorBag)
                ->withInput($this->except($this->dontFlash))
        );
    }
}
