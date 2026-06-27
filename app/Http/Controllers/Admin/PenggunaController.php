<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\Hris\Provinsi;
use App\Models\Lamaran;
use App\Models\RiwayatProsesLamaran;
use App\Models\SuratPeringatan;
use App\Models\User;
use App\Services\EmploymentStatusRefreshService;
use App\Services\Ocr\KtpIdentityValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RealRashid\SweetAlert\Facades\Alert;
use Yajra\DataTables\Facades\DataTables;

class PenggunaController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Hapus Pengguna!';
        $text = "Kamu yakin ingin menghapus pengguna ini?";
        confirmDelete($title, $text);

        if ($request->ajax()) {
            $query = User::with([
                'biodataUser' => function ($query) {
                    $query->with('getLatestRiwayatLamaran.lowongan')
                        ->withCount('getRiwayatLamaran');
                }
            ])
                ->select('users.*')
                ->selectSub(
                    Biodata::query()
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('biodata.user_id', 'users.id')
                        ->whereNotNull('status_pernyataan')
                        ->whereRaw('TRIM(status_pernyataan) <> ?', ['']),
                    'has_approved_terms'
                )
                ->where('role', '!=', 'admin');

            return DataTables::of($query)

                ->addColumn('status', function ($pengguna) {
                    $checked = $pengguna->status_akun == 1 ? 'checked' : '';

                    return '
            <label class="toggle-switch">
                <input type="checkbox"
                    class="toggle-input toggle-status"
                    data-id="' . $pengguna->id . '"
                    data-old="' . $pengguna->status_akun . '"
                    ' . $checked . '>
                <span class="toggle-slider">
                    <span class="toggle-text">ON</span>
                </span>
            </label>';
                })

                ->addColumn('status_proses', function ($pengguna) {
                    return optional(optional($pengguna->biodataUser)->getLatestRiwayatLamaran)->status_proses ?? '-';
                })

                ->addColumn('lowongan', function ($pengguna) {

                    $riwayat = optional($pengguna->biodataUser)->getLatestRiwayatLamaran;

                    if (!$riwayat || !$riwayat->lowongan) {
                        return '-';
                    }

                    $lowongan = $riwayat->lowongan;
                    $nama = substr($lowongan->nama_lowongan, 0, 15);

                    $url = route('directToLamaran', [
                        'loker_id' => $lowongan->id,
                        'user_id' => $pengguna->id
                    ]);

                    return '<a href="' . $url . '" target="_blank">' . $nama . '</a>';
                })

                ->addColumn('jumlah_melamar', function ($pengguna) {
                    return optional($pengguna->biodataUser)->get_riwayat_lamaran_count ?? 0;
                })

                ->addColumn('rekomendasi', function ($pengguna) {
                    return $pengguna->rekomendasi ?? '';
                })

                ->addColumn('riwayat', function ($pengguna) {
                    return '
            <a href="' . route('pengguna.show', $pengguna->id) . '" target="_blank"
            class="btn btn-secondary btn-sm btn-icon-split mr-2">
                <span class="icon text-white-50">
                    <i class="fas fa-history"></i>
                </span>
                <span class="text">Riwayat</span>
            </a>';
                })

                ->addColumn('aksi', function ($pengguna) {
                    $termsButton = ((int) $pengguna->has_approved_terms > 0)
                        ? '
                <a href="' . route('pengguna.syarat-ketentuan.show', $pengguna->id) . '"
                target="_blank"
                class="btn btn-info btn-sm btn-icon-split mr-2">
                    <span class="icon text-white-50">
                        <i class="fas fa-file-contract"></i>
                    </span>
                    <span class="text">S&K</span>
                </a>'
                        : '
                <button type="button"
                class="btn btn-outline-secondary btn-sm btn-icon-split mr-2"
                disabled>
                    <span class="icon text-muted">
                        <i class="fas fa-file-contract"></i>
                    </span>
                    <span class="text">Belum Setuju</span>
                </button>';

                    return '
            <div class="d-flex">
                ' . $termsButton . '

                <a href="' . route('pengguna.edit', $pengguna->id) . '"
                class="btn btn-success btn-sm btn-icon-split mr-2">
                    <span class="icon text-white-50">
                        <i class="fas fa-pen"></i>
                    </span>
                    <span class="text">Edit</span>
                </a>

                <a href="' . route('pengguna.destroy', $pengguna->id) . '"
                class="btn btn-danger btn-sm btn-icon-split"
                data-confirm-delete="true">
                    <span class="icon text-white-50">
                        <i class="fas fa-trash"></i>
                    </span>
                    <span class="text">Hapus</span>
                </a>
            </div>';
                })

                ->rawColumns(['status', 'riwayat', 'lowongan', 'aksi'])
                ->make(true);
        }

        return view('admin.pengguna.index');
    }

    public function edit($id)
    {
        // Logic to edit a user
        $pengguna = User::with('biodata')->findOrFail($id);
        $biodata = Biodata::where('user_id', $pengguna->id)->first();

        if ($biodata) {
            $provinsis = Provinsi::all();

            return view('admin.pengguna.edit', compact('pengguna', 'biodata', 'provinsis'));
        }
        Alert::warning('Peringatan', 'Biodata pengguna belum tersedia.');
        return back();
    }

    public function update(Request $request, $id)
    {
        $user = User::with('biodata')->findOrFail($id);
        $biodata = $user->biodata;

        if (! $biodata) {
            Alert::warning('Peringatan', 'Biodata pengguna belum tersedia.');
            return redirect()->route('pengguna.index');
        }

        $identityValidator = app(KtpIdentityValidator::class);

        $request->merge([
            'no_ktp' => $identityValidator->onlyDigits($request->input('no_ktp')),
        ]);

        $validatedData = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'no_ktp' => [
                'required',
                'digits:16',
                Rule::unique('users', 'no_ktp')->ignore($user->id),
                Rule::unique('biodata', 'no_ktp')->ignore($biodata->id),
            ],
            'status_akun' => ['required', 'in:0,1'],
            'no_telp' => ['required', 'digits_between:11,13'],
            'no_kk' => ['required', 'digits:16'],
            'jenis_kelamin' => ['required', 'string'],
            'agama' => ['required', 'string'],
            'tempat_lahir' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date'],
            'provinsi' => ['required', 'numeric'],
            'kabupaten' => ['required', 'numeric'],
            'kecamatan' => ['required', 'numeric'],
            'kelurahan' => ['required', 'numeric'],
            'alamat' => ['required', 'string'],
            'kode_pos' => ['required', 'digits:5'],
            'rt' => ['required', 'string', 'max:3'],
            'rw' => ['required', 'string', 'max:3'],
            'hobi' => ['required', 'string', 'max:255'],
            'golongan_darah' => ['required', 'string'],
            'tinggi_badan' => ['required', 'numeric', 'min:0'],
            'berat_badan' => ['required', 'numeric', 'min:0'],
            'pendidikan_terakhir' => ['required', 'string'],
            'nama_instansi' => ['required', 'string', 'max:255'],
            'jurusan' => ['required', 'string', 'max:255'],
            'nilai_ipk' => ['required', 'string', 'max:50'],
            'tahun_masuk' => ['nullable', 'date'],
            'tahun_lulus' => ['required', 'date'],
            'prestasi' => ['nullable', 'string'],
            'nama_ibu' => ['required', 'string', 'max:255'],
            'nama_ayah' => ['required', 'string', 'max:255'],
            'status_pernikahan' => ['required', 'string'],
            'tanggal_nikah' => ['nullable', 'date', 'required_if:status_pernikahan,Kawin'],
            'nama_pasangan' => ['nullable', 'string', 'max:255', 'required_if:status_pernikahan,Kawin'],
            'jumlah_anak' => ['nullable', 'integer', 'min:0'],
            'nama_anak_1' => ['nullable', 'string', 'max:255'],
            'nama_anak_2' => ['nullable', 'string', 'max:255'],
            'nama_anak_3' => ['nullable', 'string', 'max:255'],
            'nama_kontak_darurat' => ['required', 'string', 'max:255'],
            'no_telp_darurat' => ['required', 'digits_between:11,13'],
            'status_hubungan' => ['required', 'string'],
        ], [
            'required' => ':attribute wajib diisi.',
            'required_if' => ':attribute wajib diisi saat :other adalah :value.',
            'digits' => ':attribute harus terdiri dari :digits digit.',
            'digits_between' => ':attribute harus terdiri dari :min sampai :max digit.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'numeric' => ':attribute harus berupa angka.',
            'integer' => ':attribute harus berupa angka bulat.',
            'max' => ':attribute maksimal :max karakter.',
            'min' => ':attribute minimal :min.',
            'unique' => ':attribute sudah digunakan oleh pengguna lain.',
            'in' => ':attribute tidak valid.',
        ], [
            'nama' => 'Nama',
            'no_ktp' => 'No KTP',
            'status_akun' => 'Status Akun',
            'no_telp' => 'No Telp',
            'no_kk' => 'No Kartu Keluarga',
            'jenis_kelamin' => 'Jenis Kelamin',
            'agama' => 'Agama',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'provinsi' => 'Provinsi',
            'kabupaten' => 'Kabupaten/Kota',
            'kecamatan' => 'Kecamatan',
            'kelurahan' => 'Kelurahan/Desa',
            'alamat' => 'Alamat Lengkap',
            'kode_pos' => 'Kode Pos',
            'rt' => 'RT',
            'rw' => 'RW',
            'hobi' => 'Hobi',
            'golongan_darah' => 'Golongan Darah',
            'tinggi_badan' => 'Tinggi Badan',
            'berat_badan' => 'Berat Badan',
            'pendidikan_terakhir' => 'Pendidikan Terakhir',
            'nama_instansi' => 'Nama Sekolah/Kampus',
            'jurusan' => 'Jurusan',
            'nilai_ipk' => 'IPK/Nilai Ijazah',
            'tahun_masuk' => 'Tahun Masuk',
            'tahun_lulus' => 'Tahun Lulus',
            'prestasi' => 'Prestasi',
            'nama_ibu' => 'Nama Ibu',
            'nama_ayah' => 'Nama Ayah',
            'status_pernikahan' => 'Status Pernikahan',
            'tanggal_nikah' => 'Tanggal Pernikahan',
            'nama_pasangan' => 'Nama Suami/Istri',
            'jumlah_anak' => 'Jumlah Anak',
            'nama_anak_1' => 'Nama Anak ke-1',
            'nama_anak_2' => 'Nama Anak ke-2',
            'nama_anak_3' => 'Nama Anak ke-3',
            'nama_kontak_darurat' => 'Nama Kontak Darurat',
            'no_telp_darurat' => 'No Telepon Darurat',
            'status_hubungan' => 'Status Hubungan',
        ]);

        if (! $identityValidator->isPlausibleNik($validatedData['no_ktp'])) {
            throw ValidationException::withMessages([
                'no_ktp' => 'Nomor KTP tidak valid. Pastikan NIK terdiri dari 16 digit dan sesuai format KTP Indonesia.',
            ]);
        }

        $oldUserKtp = $identityValidator->onlyDigits($user->no_ktp);
        $oldBiodataKtp = $identityValidator->onlyDigits($biodata->no_ktp);
        $oldDirectoryKtp = $oldBiodataKtp ?: $oldUserKtp;
        $newKtp = $validatedData['no_ktp'];

        if (
            $oldUserKtp
            && $oldUserKtp !== $oldDirectoryKtp
            && ! File::isDirectory(public_path($oldDirectoryKtp))
            && File::isDirectory(public_path($oldUserKtp))
        ) {
            $oldDirectoryKtp = $oldUserKtp;
        }

        $oldPath = $oldDirectoryKtp ? public_path($oldDirectoryKtp) : null;
        $newPath = public_path($newKtp);
        $shouldMoveDirectory = $oldDirectoryKtp
            && $oldDirectoryKtp !== $newKtp
            && File::isDirectory($oldPath);

        if ($shouldMoveDirectory && File::exists($newPath)) {
            throw ValidationException::withMessages([
                'no_ktp' => 'Nomor KTP ini tidak dapat digunakan karena folder dokumen dengan nomor tersebut sudah ada.',
            ]);
        }

        $directoryMoved = false;

        try {
            DB::beginTransaction();

            $user->forceFill([
                'name' => $validatedData['nama'],
                'no_ktp' => $newKtp,
                'status_akun' => $validatedData['status_akun'],
            ])->save();

            $biodata->forceFill([
                'no_ktp' => $newKtp,
                'no_telp' => $validatedData['no_telp'],
                'no_kk' => $validatedData['no_kk'],
                'jenis_kelamin' => $validatedData['jenis_kelamin'],
                'agama' => $validatedData['agama'],
                'tempat_lahir' => $validatedData['tempat_lahir'],
                'tanggal_lahir' => $validatedData['tanggal_lahir'],
                'provinsi' => $validatedData['provinsi'],
                'kabupaten' => $validatedData['kabupaten'],
                'kecamatan' => $validatedData['kecamatan'],
                'kelurahan' => $validatedData['kelurahan'],
                'alamat' => $validatedData['alamat'],
                'kode_pos' => $validatedData['kode_pos'],
                'rt' => $validatedData['rt'],
                'rw' => $validatedData['rw'],
                'hobi' => $validatedData['hobi'],
                'golongan_darah' => $validatedData['golongan_darah'],
                'tinggi_badan' => $validatedData['tinggi_badan'],
                'berat_badan' => $validatedData['berat_badan'],
                'pendidikan_terakhir' => $validatedData['pendidikan_terakhir'],
                'nama_instansi' => ucwords($validatedData['nama_instansi']),
                'jurusan' => ucwords($validatedData['jurusan']),
                'nilai_ipk' => $validatedData['nilai_ipk'],
                'tahun_masuk' => $validatedData['tahun_masuk'] ?? null,
                'tahun_lulus' => $validatedData['tahun_lulus'],
                'prestasi' => $validatedData['prestasi'] ?? null,
                'nama_ibu' => ucwords($validatedData['nama_ibu']),
                'nama_ayah' => ucwords($validatedData['nama_ayah']),
                'status_pernikahan' => $validatedData['status_pernikahan'],
                'tanggal_nikah' => $validatedData['tanggal_nikah'] ?? null,
                'nama_pasangan' => ! empty($validatedData['nama_pasangan']) ? ucwords($validatedData['nama_pasangan']) : null,
                'jumlah_anak' => $validatedData['jumlah_anak'] ?? null,
                'nama_anak_1' => $validatedData['nama_anak_1'] ?? null,
                'nama_anak_2' => $validatedData['nama_anak_2'] ?? null,
                'nama_anak_3' => $validatedData['nama_anak_3'] ?? null,
                'nama_kontak_darurat' => ucwords($validatedData['nama_kontak_darurat']),
                'no_telepon_darurat' => $validatedData['no_telp_darurat'],
                'status_hubungan' => $validatedData['status_hubungan'],
            ])->save();

            if ($shouldMoveDirectory && ! File::moveDirectory($oldPath, $newPath)) {
                throw new \RuntimeException('Gagal memindahkan folder dokumen pengguna.');
            }

            $directoryMoved = $shouldMoveDirectory;

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($directoryMoved && File::isDirectory($newPath) && ! File::exists($oldPath)) {
                File::moveDirectory($newPath, $oldPath);
            }

            report($e);

            Alert::error('Gagal', 'Pengguna gagal diperbarui. Silakan cek kembali data yang diinput.');
            return redirect()->back()->withInput();
        }

        if ($oldUserKtp !== $newKtp || $oldBiodataKtp !== $newKtp) {
            app(EmploymentStatusRefreshService::class)->refreshUser($user->fresh()->load('biodata'));
        }

        Alert::success('Berhasil', 'Pengguna berhasil diperbarui.');
        return redirect()->route('pengguna.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function show($id)
    {
        // Logic to show user details
        $user = User::with('biodata', 'biodataUser.getRiwayatLamaran.lowongan')->findOrFail($id);

        return view('admin.pengguna.show', compact('user'));
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $user = User::findOrFail($id);

            $biodata = Biodata::where('user_id', $user->id)->first();

            if ($biodata) {
                deleteImageBiodata($biodata);

                Lamaran::where('biodata_id', $biodata->id)->delete();

                $biodata->delete();
            }

            RiwayatProsesLamaran::where('user_id', $user->id)->delete();

            SuratPeringatan::where('user_id', $user->id)->delete();

            $user->delete();

            DB::commit();

            Alert::success('Berhasil', 'Pengguna berhasil dihapus.');
            return redirect()->route('pengguna.index')->with('success', 'Pengguna berhasil dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            Alert::error('Gagal', 'Pengguna gagal dihapus.');
            return redirect()->back()->with('error', 'Pengguna gagal dihapus.');
        }
    }

    public function updateStatusAkun(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
            'status_akun' => 'required|in:0,1',
        ]);

        User::where('id', $request->id)->update([
            'status_akun' => $request->status_akun
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status akun berhasil diperbarui'
        ]);
    }
}
