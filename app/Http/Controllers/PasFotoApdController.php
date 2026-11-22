<?php

namespace App\Http\Controllers;

use App\Services\PasFotoApdService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PasFotoApdController extends Controller
{
    public function index(Request $request, PasFotoApdService $photos)
    {
        $biodata = $photos->eligibleBiodata($request->user());
        abort_unless($biodata, 403, 'Pas foto APD dapat diperbarui setelah Anda tercatat aktif bekerja.');

        return view('user.pas-foto-apd.index', compact('biodata'));
    }

    public function store(Request $request, PasFotoApdService $photos)
    {
        $biodata = $photos->eligibleBiodata($request->user());
        abort_unless($biodata, 403, 'Pas foto APD dapat diperbarui setelah Anda tercatat aktif bekerja.');

        $request->validate([
            'pas_foto_apd' => [
                'bail', 'required', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png',
                'max:2048', 'dimensions:min_width=300,min_height=300,max_width=8000,max_height=8000',
            ],
            'konfirmasi_apd' => ['accepted'],
        ], [
            'pas_foto_apd.required' => 'Pilih file pas foto terlebih dahulu.',
            'pas_foto_apd.uploaded' => 'Unggahan gagal. Pastikan file dapat dibuka dan ukurannya maksimal 2 MB.',
            'pas_foto_apd.image' => 'Pas foto harus berupa gambar JPG atau PNG.',
            'pas_foto_apd.mimes' => 'Pas foto harus berupa gambar JPG atau PNG.',
            'pas_foto_apd.extensions' => 'Pas foto harus berupa gambar JPG atau PNG.',
            'pas_foto_apd.max' => 'Ukuran pas foto maksimal 2 MB.',
            'pas_foto_apd.dimensions' => 'Lebar dan tinggi pas foto masing-masing harus antara 300 dan 8.000 piksel.',
            'konfirmasi_apd.accepted' => 'Centang konfirmasi bahwa foto memenuhi panduan.',
        ]);

        try {
            $photos->replace($biodata, $request->file('pas_foto_apd'));
        } catch (Throwable $exception) {
            Log::error('Penyimpanan pas foto APD gagal.', [
                'type' => get_class($exception),
                'code' => $exception->getCode(),
            ]);

            return redirect()->route('pas-foto-apd.index')
                ->withErrors(['pas_foto_apd' => 'Pas foto belum tersimpan. Pilih kembali file dan coba lagi.'])
                ->withInput();
        }

        return redirect()->route('pas-foto-apd.index')
            ->with('apd_photo_success', 'Pas foto APD berhasil disimpan di V-Hire.');
    }
}
