<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerification;
use App\Models\Hris\Employee;
use App\Models\Hris\Peringatan;
use App\Models\SuratPeringatan;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PendaftaranController extends Controller
{
    public function index()
    {
        $title = 'Delete Data!';
        $text = "Are you sure you want to delete?";
        confirmDelete($title, $text);

        return view('user.pendaftaran.index');
    }

    public function store(Request $request)
    {
        // Normalize KTP (hindari spasi & mismatch)
        $request->merge([
            'no_ktp' => preg_replace('/\s+/', '', $request->no_ktp)
        ]);

        // Validate input
        $validatedData = $request->validate([
            'no_ktp'    => 'bail|required|digits:16|unique:users,no_ktp',
            'email'     => 'bail|required|email|max:255|unique:users,email',
            'password'  => 'required|min:6|confirmed',
            'name'      => 'required|string|max:255',
        ], [
            'no_ktp.required' => 'Nomor KTP wajib diisi.',
            'no_ktp.digits'   => 'Nomor KTP harus terdiri dari 16 digit.',
            'no_ktp.unique'   => 'Nomor KTP sudah terdaftar.',
            'email.unique'    => 'Email sudah terdaftar.',
            'email.required'  => 'Alamat email wajib diisi.',
            'email.email'     => 'Format alamat email tidak valid.',
            'email.max'       => 'Panjang alamat email maksimal 255 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai!',
            'name.required'   => 'Nama wajib diisi.',
        ]);

        try {
            DB::transaction(function () use ($validatedData, $request, &$user_baru) {

                // Cari employee (jika ada)
                $employee = Employee::where('no_ktp', $validatedData['no_ktp'])
                    ->orderByRaw('LEFT(nik, 4) DESC')
                    ->first();

                // Create user
                $user_baru = User::create([
                    'no_ktp' => $validatedData['no_ktp'],
                    'name' => strtoupper($request->name),
                    'email' => $validatedData['email'],
                    'password' => bcrypt($request->password),
                    'status_akun' => 0,
                    'status_pelamar' => $employee ? $employee->status_resign : null,
                    'tanggal_resign' => $employee ? $employee->tgl_resign : null,
                    'ket_resign' => $employee ? $employee->alasan_resign : null,
                    'role' => 'user',
                    'area_kerja' => $employee ? $employee->area_kerja : null,
                    'email_verifikasi_token' => md5($validatedData['no_ktp'] . now())
                ]);

                // Copy SP jika ada
                if ($employee) {
                    $peringatan = Peringatan::where('nik_karyawan', $employee->nik)->get();

                    foreach ($peringatan as $data) {
                        SuratPeringatan::create([
                            'user_id' => $user_baru->id,
                            'level_sp' => $data->level_sp,
                            'ket_sp' => $data->keterangan,
                            'tanggal_mulai_sp' => $data->tgl_mulai,
                            'tanggal_berakhir_sp' => $data->tgl_berakhir,
                        ]);
                    }
                }
            });

            // Kirim email setelah commit (lebih aman)
            Mail::to($validatedData['email'])->send(new EmailVerification($user_baru));

            Alert::success('Berhasil', 'Pendaftaran berhasil! Silakan verifikasi email kamu.');
            return redirect()->route('login');
        } catch (\Illuminate\Database\QueryException $e) {

            // Handle duplicate entry (MySQL error 1062)
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
                Alert::error('Gagal', 'Nomor KTP atau Email sudah terdaftar.');
                return back()->withInput();
            }

            throw $e;
        } catch (\Exception $e) {

            Log::error('Register Error: ' . $e->getMessage());

            Alert::error('Gagal', 'Terjadi kesalahan sistem. Silakan coba lagi.');
            return back()->withInput();
        }
    }

    public function konfirmasiEmail($id)
    {
        $check = User::where('id', $id)->first();

        if (!$check) {
            Alert::error('Opps!', 'Akun tidak ditemukan silakan daftar ulang');
            return redirect('login');
        }

        if ($check->status_akun == 1) {
            Alert::error('Opps!', 'Akun kamu telah aktif silakan login');
            return redirect('login');
        }

        User::where('id', $id)->update([
            'email_verified_at' => Carbon::now(),
            'status_akun' => 1
        ]);

        return view('verifikasi-berhasil');
    }

    public function konfirmasiEmailToken($token)
    {
        $check = User::where('email_verifikasi_token', $token)->first();

        if (!$check) {
            Alert::error('Opps!', 'Akun tidak ditemukan silakan daftar ulang');
            return redirect('login');
        }

        if ($check->status_akun == 1) {
            Alert::error('Opps!', 'Akun kamu telah aktif silakan login');
            return redirect('login');
        }

        User::where('email_verifikasi_token', $token)->update([
            'email_verified_at' => Carbon::now(),
            'status_akun' => 1
        ]);

        return view('verifikasi-berhasil');
    }
}
