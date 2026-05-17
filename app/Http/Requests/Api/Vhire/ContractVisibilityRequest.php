<?php

namespace App\Http\Requests\Api\Vhire;

use Illuminate\Foundation\Http\FormRequest;

class ContractVisibilityRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'visible_in_vhire' => ['required', 'boolean'],
            'hidden_reason' => ['required_if:visible_in_vhire,false,0', 'nullable', 'string', 'max:255'],
        ];
    }
}
