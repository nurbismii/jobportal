<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreAssessmentLinkRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()?->canAccessAdminRoute('assessment-links.store') ?? false;
    }

    public function rules()
    {
        return [
            'assessment_type' => ['required', Rule::in(['kesehatan', 'lapangan', 'mcu'])],
            'pin' => ['required', 'string', 'min:6', 'max:32'],
            'expires_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'field_test' => ['exclude_unless:assessment_type,lapangan', 'nullable', 'array:template,location,test_date,maxima,signers'],
            'field_test.template' => ['nullable', Rule::in(array_keys(app(\App\Services\FieldTestService::class)->templates()))],
            'selected_ids' => ['exclude_if:assessment_type,mcu', 'required', 'array', 'min:1'],
            'selected_ids.*' => ['required', 'integer', 'distinct', 'exists:lamaran,id'],
            'fields' => ['exclude_if:assessment_type,mcu', 'nullable', 'array', 'max:20'],
            'fields.*' => ['required', 'array'],
            'fields.*.id' => ['required', 'string', 'max:100'],
            'fields.*.label' => ['required', 'string', 'max:100'],
            'fields.*.type' => ['required', Rule::in(['select', 'number', 'text'])],
            'fields.*.required' => ['nullable', 'boolean'],
            'fields.*.options' => ['nullable', 'array', 'max:20'],
            'fields.*.options.*' => ['required', 'string', 'max:100'],
            'eligibility_field_id' => ['exclude_if:assessment_type,mcu', 'nullable', 'string', 'max:100'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->redirector->to($this->getRedirectUrl())
                ->withErrors($validator, $this->errorBag)
                ->withInput($this->inputForRedisplay())
        );
    }

    public function inputForRedisplay(): array
    {
        $input = $this->except(['pin', 'field_test.signers']);
        foreach (\App\Services\FieldTestService::ROLES as $role => $label) {
            $name = $this->input('field_test.signers.'.$role.'.name');
            if (is_string($name)) {
                data_set($input, 'field_test.signers.'.$role.'.name', $name);
            }
        }
        return $input;
    }
}
