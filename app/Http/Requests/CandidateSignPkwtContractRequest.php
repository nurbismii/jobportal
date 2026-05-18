<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CandidateSignPkwtContractRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'consent' => ['accepted'],
            'signature_data' => [
                'required',
                'string',
                'regex:/^data:image\/png;base64,/',
                'max:1500000',
            ],
        ];
    }

    public function messages()
    {
        return [
            'consent.accepted' => 'Anda perlu menyetujui pernyataan sebelum menandatangani.',
            'signature_data.required' => 'Tanda tangan wajib diisi.',
            'signature_data.regex' => 'Format tanda tangan tidak valid.',
        ];
    }
}
