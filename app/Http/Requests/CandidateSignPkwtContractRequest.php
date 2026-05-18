<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CandidateSignPkwtContractRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'candidate_signature' => trim((string) $this->input('candidate_signature')),
        ]);
    }

    public function rules()
    {
        return [
            'candidate_signature' => ['required', 'string', 'max:255'],
            'agreement' => ['accepted'],
        ];
    }
}
