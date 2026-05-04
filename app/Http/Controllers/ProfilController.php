<?php

namespace App\Http\Controllers;

use App\Models\Biodata;
use App\Models\Lamaran;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\EmploymentStatusRefreshService;
use App\Services\Ocr\KtpIdentityValidator;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class ProfilController extends Controller
{
    private function ensureNikCanBeChanged(?Biodata $biodata, string $oldKtp, string $newKtp, KtpIdentityValidator $identityValidator): void
    {
        if ($oldKtp === $newKtp) {
            return;
        }

        if (! $identityValidator->isPlausibleNik($newKtp)) {
            throw ValidationException::withMessages([
                'no_ktp' => 'Nomor KTP tidak valid. Pastikan NIK terdiri dari 16 digit dan sesuai format KTP Indonesia.',
            ]);
        }

        if (User::latestHrisEmployeeByNoKtp($oldKtp)) {
            throw ValidationException::withMessages([
                'no_ktp' => 'Nomor KTP tidak dapat diubah karena sudah terhubung dengan riwayat karyawan di HRIS.',
            ]);
        }

        if ($biodata && ($biodata->ktp || $biodata->ocr_ktp || Lamaran::where('biodata_id', $biodata->id)->exists())) {
            throw ValidationException::withMessages([
                'no_ktp' => 'Nomor KTP tidak dapat diubah setelah KTP/lamaran tersimpan. Hubungi HR/admin jika ada koreksi identitas.',
            ]);
        }
    }

    private function ensureNameMatchesHris(string $name, string $noKtp, KtpIdentityValidator $identityValidator): void
    {
        $employee = User::latestHrisEmployeeByNoKtp($noKtp);

        if ($employee && ! $identityValidator->namesMatch($name, (string) $employee->nama_karyawan, 78)) {
            throw ValidationException::withMessages([
                'nama' => 'Nama tidak sesuai dengan riwayat karyawan di HRIS untuk nomor KTP ini.',
            ]);
        }
    }

    public function index()
    {
        return view('user.profil.index');
    }

    public function update(Request $request, $id)
    {
        if ((int) $id !== (int) auth()->id()) {
            abort(403);
        }

        $user = $request->user();
        $identityValidator = app(KtpIdentityValidator::class);
        $identityUpdateLocked = $user->hasActiveEmploymentStatusLock();

        if ($identityUpdateLocked) {
            $request->merge([
                'nama' => $user->name,
                'no_ktp' => $user->no_ktp,
                'email' => $user->email,
            ]);
        }

        $request->merge([
            'no_ktp' => $identityValidator->onlyDigits($request->no_ktp),
        ]);

        $request->validate([
            'nama' => 'required|string|max:255',
            'no_ktp' => 'required|digits:16|unique:users,no_ktp,' . $user->id,
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nama.string' => 'Nama lengkap harus berupa teks.',
            'nama.max' => 'Nama maksimal 255 karakter.',

            'no_ktp.required' => 'Nomor KTP wajib diisi.',
            'no_ktp.digits' => 'Nomor KTP harus terdiri dari 16 digit.',
            'no_ktp.unique' => 'Nomor KTP ini sudah digunakan oleh akun lain.',

            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email terlalu panjang.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',

            'password.min' => 'Password minimal harus 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $oldKtp = $identityValidator->onlyDigits($user->no_ktp);
        $newKtp = $identityValidator->onlyDigits($request->no_ktp);
        $biodata = Biodata::where('user_id', $user->id)->first();

        $this->ensureNikCanBeChanged($biodata, $oldKtp, $newKtp, $identityValidator);
        $this->ensureNameMatchesHris($request->nama, $newKtp, $identityValidator);

        if ($oldKtp !== $newKtp) {
            $newPath = public_path($newKtp);

            if (File::exists($newPath)) {
                throw ValidationException::withMessages([
                    'no_ktp' => 'Nomor KTP ini tidak dapat digunakan karena folder dokumen dengan nomor tersebut sudah ada.',
                ]);
            }
        }

        $user->name = $request->nama;
        $user->no_ktp = $newKtp;

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        Biodata::where('user_id', $user->id)->update([
            'no_ktp' => $newKtp,
        ]);

        if ($oldKtp !== $newKtp) {
            $oldPath = public_path($oldKtp);
            $newPath = public_path($newKtp);

            if (File::exists($oldPath)) {
                File::moveDirectory($oldPath, $newPath);
            }
        }

        $user->save();

        if ($oldKtp !== $newKtp) {
            app(EmploymentStatusRefreshService::class)->refreshUser($user->fresh()->load('biodata'));
        }

        Alert::success('Berhasil', 'Profil telah diperbarui');
        return redirect()->back();
    }
}
