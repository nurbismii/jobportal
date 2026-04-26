<?php

namespace App\Http\Requests\Api\Internal;

use Illuminate\Foundation\Http\FormRequest;

class CandidateDocumentsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'no_ktp' => ['required', 'string', 'max:32'],
        ];
    }
}
