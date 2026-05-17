<?php

namespace App\Http\Requests\Api\Vhire;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HrisContractImportRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'hris_contract_id' => ['nullable', 'string', 'max:100'],
            'vhire_candidate_id' => ['required', 'string', 'max:100'],
            'candidate_code' => ['required', 'string', 'max:100'],
            'no_ktp' => ['required', 'digits:16'],
            'nama' => ['required', 'string', 'max:255'],
            'kode_kontrak' => ['required_without:no_pkwt', 'nullable', 'string', 'max:100'],
            'no_pkwt' => ['required_without:kode_kontrak', 'nullable', 'string', 'max:100'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'departemen' => ['nullable', 'string', 'max:255'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'tanggal_mulai_kontrak' => ['required', 'date'],
            'tanggal_akhir_kontrak' => ['nullable', 'date', 'after_or_equal:tanggal_mulai_kontrak'],
            'duration_value' => ['nullable', 'integer', 'min:1', 'max:120'],
            'duration_unit' => ['nullable', Rule::in(['day', 'week', 'month', 'year'])],
            'contract_duration_value' => ['nullable', 'integer', 'min:1', 'max:120'],
            'contract_duration_unit' => ['nullable', Rule::in(['day', 'week', 'month', 'year'])],
            'durasi_kontrak' => ['nullable', 'string', 'max:100'],
            'gaji' => ['nullable', 'numeric', 'min:0'],
            'status_tanda_tangan' => ['nullable', 'string', 'max:50'],
            'signature_status' => ['nullable', Rule::in(['draft', 'waiting_signature', 'signed', 'rejected', 'cancelled'])],
            'signing_method' => ['nullable', Rule::in(['electronic', 'manual'])],
            'signed_at' => ['nullable', 'date'],
            'signed_by_source' => ['nullable', Rule::in(['vhire', 'manual_upload', 'admin'])],
            'visible_in_vhire' => ['nullable', 'boolean'],
            'hidden_reason' => ['nullable', 'string', 'max:255'],
            'hidden_at' => ['nullable', 'date'],
            'employee_nik' => ['nullable', 'string', 'max:50'],
            'activated_as_employee_at' => ['nullable', 'date'],
            'manual_signed_file_base64' => ['nullable', 'string'],
            'manual_signed_file_name' => ['nullable', 'string', 'max:255'],
            'manual_signed_file_mime' => ['nullable', 'string', 'max:100'],
            'manual_uploaded_by' => ['nullable', 'integer'],
            'manual_uploaded_at' => ['nullable', 'date'],
            'manual_verification_status' => ['nullable', Rule::in(['pending_review', 'verified', 'rejected'])],
            'manual_note' => ['nullable', 'string', 'max:5000'],
            'contract_file_base64' => ['nullable', 'string'],
            'contract_file_name' => ['nullable', 'string', 'max:255'],
            'contract_file_mime' => ['nullable', 'string', 'max:100'],
            'contract_content' => ['nullable', 'string'],
        ];
    }
}
