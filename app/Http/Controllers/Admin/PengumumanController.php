<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;

class PengumumanController extends Controller
{
    private const THUMBNAIL_DIRECTORY = 'thumbnail';

    public function index()
    {
        $title = 'Hapus Pengumuman!';
        $text = "Kamu yakin ingin menghapus pengumuman ini?";
        confirmDelete($title, $text);

        $pengumumans = Pengumuman::orderBy('id', 'desc')->get();

        return view('admin.pengumuman.index', compact('pengumumans'))->with('no');
    }

    public function create()
    {
        return view('admin.pengumuman.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatePengumuman($request, true);
        $fileName = $this->storeThumbnail($request->file('thumbnail'));

        Pengumuman::create([
            'pengumuman' => $validated['pengumuman'],
            'thumbnail' => $fileName,
            'keterangan' => $validated['keterangan'],
        ]);

        Alert::success('Berhasil', 'Pengumuman berhasil dibuat');
        return redirect()->route('pengumumans.index');
    }

    public function edit($id)
    {
        $pengumuman = Pengumuman::findOrFail($id);
        return view('admin.pengumuman.edit', compact('pengumuman'));
    }

    public function update(Request $request, $id)
    {
        $validated = $this->validatePengumuman($request, false);
        $pengumuman = Pengumuman::findOrFail($id);
        $pengumuman->pengumuman = $validated['pengumuman'];
        $pengumuman->keterangan = $validated['keterangan'];

        if ($request->hasFile('thumbnail')) {
            $this->deleteThumbnail($pengumuman->thumbnail);
            $pengumuman->thumbnail = $this->storeThumbnail($request->file('thumbnail'));
        }

        $pengumuman->save();
        Alert::success('Berhasil', 'Pengumuman berhasil diperbarui');
        return redirect()->route('pengumumans.index');
    }

    public function destroy($id)
    {
        $pengumuman = Pengumuman::findOrFail($id);
        $this->deleteThumbnail($pengumuman->thumbnail);
        $pengumuman->delete();

        Alert::success('Berhasil', 'Pengumuman berhasil dihapus');
        return redirect()->route('pengumumans.index');
    }

    private function validatePengumuman(Request $request, bool $thumbnailRequired): array
    {
        return $request->validate([
            'pengumuman' => ['required', 'string', 'max:255'],
            'thumbnail' => [$thumbnailRequired ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'keterangan' => ['required', 'string'],
        ], [
            'pengumuman.required' => 'Judul pengumuman wajib diisi.',
            'thumbnail.required' => 'Thumbnail wajib diunggah.',
            'thumbnail.image' => 'Thumbnail harus berupa gambar.',
            'thumbnail.mimes' => 'Thumbnail harus berformat JPG, JPEG, atau PNG.',
            'thumbnail.max' => 'Ukuran thumbnail maksimal 2 MB.',
            'keterangan.required' => 'Keterangan pengumuman wajib diisi.',
        ]);
    }

    private function storeThumbnail($file): string
    {
        $path = public_path(self::THUMBNAIL_DIRECTORY);

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $fileName = date('YmdHis') . '-' . Str::random(10) . '.' . strtolower($file->getClientOriginalExtension());
        $file->move($path, $fileName);

        return $fileName;
    }

    private function deleteThumbnail(?string $fileName): void
    {
        if (!$fileName) {
            return;
        }

        $filePath = public_path(self::THUMBNAIL_DIRECTORY . DIRECTORY_SEPARATOR . $fileName);

        if (is_file($filePath)) {
            unlink($filePath);
        }
    }
}
