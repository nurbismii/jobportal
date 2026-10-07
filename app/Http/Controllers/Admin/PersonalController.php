<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\File;
use ZipArchive;

class PersonalController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = Biodata::with('user')
                ->whereHas('user', function ($q) {
                    $q->where('status_akun', 1);
                });

            return DataTables::of($query)

                ->addIndexColumn()

                ->addColumn('nama', function ($bio) {
                    return $bio->user->name ?? '-';
                })

                ->addColumn('surat_lamaran', function ($bio) {
                    return $this->dokumenLink($bio, $bio->surat_lamaran);
                })

                ->addColumn('cv', function ($bio) {
                    return $this->dokumenLink($bio, $bio->cv);
                })

                ->addColumn('ktp', function ($bio) {
                    return $this->dokumenLink($bio, $bio->ktp);
                })

                ->addColumn('sim_b_2', function ($bio) {
                    return $this->dokumenLink($bio, $bio->sim_b_2);
                })

                ->addColumn('kk', function ($bio) {
                    return $this->dokumenLink($bio, $bio->kk);
                })

                ->addColumn('ijazah', function ($bio) {
                    return $this->dokumenLink($bio, $bio->ijazah);
                })

                ->addColumn('skck', function ($bio) {
                    return $this->dokumenLink($bio, $bio->skck);
                })

                ->addColumn('ak1', function ($bio) {
                    return $this->dokumenLink($bio, $bio->ak1);
                })

                ->addColumn('vaksin', function ($bio) {
                    return $this->dokumenLink($bio, $bio->sertifikat_vaksin);
                })

                ->addColumn('npwp', function ($bio) {
                    return $this->dokumenLink($bio, $bio->npwp);
                })

                ->addColumn('pas_foto', function ($bio) {
                    return $this->dokumenLink($bio, $bio->pas_foto);
                })

                ->addColumn('sertifikat_pendukung', function ($bio) {
                    return $this->dokumenLink($bio, $bio->sertifikat_pendukung);
                })

                ->addColumn('download', function ($bio) {

                    return '
                <a href="' . route('pelamar.download', $bio->id) . '" 
                class="btn btn-sm btn-primary">

                <i class="fas fa-download"></i> ZIP

                </a>';
                })

                ->rawColumns([
                    'surat_lamaran',
                    'cv',
                    'ktp',
                    'sim_b_2',
                    'kk',
                    'ijazah',
                    'skck',
                    'ak1',
                    'vaksin',
                    'npwp',
                    'pas_foto',
                    'sertifikat_pendukung',
                    'download'
                ])

                ->make(true);
        }

        return view('admin.personal-file.index');
    }

    private function dokumenLink($bio, $file)
    {
        if (!$file) return '-';

        $url = candidate_document_url($bio->no_ktp, $file);

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $icon = '<i class="fas fa-image text-success"></i>';
        } elseif ($ext == 'pdf') {
            $icon = '<i class="fas fa-file-pdf text-danger"></i>';
        } else {
            $icon = '<i class="fas fa-file"></i>';
        }

        return '
        <a href="javascript:void(0)" 
           class="preview-file"
           data-file="' . e($url) . '"
           data-title="' . e($file) . '">

           ' . $icon . ' ' . e($file) . '

        </a>';
    }

    public function downloadZip($id)
    {
        $bio = Biodata::findOrFail($id);

        $zipName = 'dokumen_' . $bio->no_ktp . '.zip';
        $zipPath = storage_path('app/private/' . \Illuminate\Support\Str::uuid() . '.zip');
        File::ensureDirectoryExists(dirname($zipPath));

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {

            foreach (['cv', 'pas_foto', 'surat_lamaran', 'ijazah', 'ktp', 'sim_b_2', 'sio', 'skck',
                'sertifikat_vaksin', 'kartu_keluarga', 'npwp', 'ak1', 'sertifikat_pendukung'] as $field) {
                if (! $bio->{$field}) continue;
                $path = candidate_document_path($bio->no_ktp, $bio->{$field});
                if (is_file($path)) $zip->addFile($path, basename($path));
            }

            $zip->close();
        }

        abort_unless(is_file($zipPath), 500, 'Gagal membuat arsip dokumen.');
        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }
}
