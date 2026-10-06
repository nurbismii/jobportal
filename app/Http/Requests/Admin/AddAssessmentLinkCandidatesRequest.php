<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AddAssessmentLinkCandidatesRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()?->canAccessAdminRoute('assessment-links.candidates.store') ?? false;
    }

    public function rules()
    {
        return [
            'lamaran_ids' => ['required', 'array', 'min:1', 'max:100'],
            'lamaran_ids.*' => ['required', 'integer', 'distinct', 'exists:lamaran,id'],
        ];
    }
}
