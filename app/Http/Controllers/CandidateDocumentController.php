<?php

namespace App\Http\Controllers;

use App\Models\Biodata;
use Illuminate\Http\Request;

class CandidateDocumentController extends Controller
{
    public function show(Request $request, string $noKtp, string $file)
    {
        $user = $request->user();
        $biodata = Biodata::where('no_ktp', $noKtp)->firstOrFail();
        abort_unless((int) $biodata->user_id === (int) $user->id
            || ($user->isInternalUser() && collect(['personal', 'lamaran', 'pengguna', 'kandidat', 'peralihan'])
                ->contains(fn($module) => $user->hasModulePermission($module, 'view'))), 403);
        $fields = [
            'cv',
            'pas_foto',
            'pas_foto_apd',
            'surat_lamaran',
            'ijazah',
            'ktp',
            'sim_b_2',
            'sio',
            'skck',
            'sertifikat_vaksin',
            'kartu_keluarga',
            'npwp',
            'ak1',
            'sertifikat_pendukung'
        ];
        abort_unless(collect($fields)->contains(fn($field) => $biodata->{$field} === $file), 404);
        $isApdPhoto = $biodata->pas_foto_apd === $file;
        $path = candidate_document_path($noKtp, $file, ! $isApdPhoto);
        abort_unless(is_file($path), 404);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        abort_unless(in_array($mime, $isApdPhoto ? ['image/jpeg', 'image/png'] : ['application/pdf', 'image/jpeg', 'image/png'], true), 415);
        return response()->file($path, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store'
        ]);
    }
}
