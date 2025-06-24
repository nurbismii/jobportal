<?php

namespace App\Http\Controllers;

use App\Mail\SendEmailVerification;
use App\Models\Hris\Employee;
use App\Models\Hris\Peringatan;
use App\Models\SuratPeringatan;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Auth\Events\Registered;
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
        $tidak_aktif = 0;

        // Validate the request data
        $validatedData = $request->validate([
            'no_ktp' => 'required|string',
            'email' => 'required|email|max:255',
        ]);

        // Cek KTP sudah terdaftar
        if (User::where('no_ktp', $validatedData['no_ktp'])->exists()) {
            Alert::error('Gagal', 'Nomor KTP sudah terdaftar!');
            return redirect()->back();
        }

        // Cek konfirmasi password
        if ($request->password !== $request->password_confirmation) {
            Alert::error('Gagal', 'Konfirmasi password tidak sesuai!');
            return redirect()->back();
        }

        // Ambil data karyawan jika ada
        $employee = Employee::where('no_ktp', $validatedData['no_ktp'])->first();

        // Gunakan transaksi untuk menjamin konsistensi data
        DB::beginTransaction();

        try {
            // Buat akun user baru
            $user_baru = User::create([
                'no_ktp' => $validatedData['no_ktp'],
                'name' => strtoupper($request->first_name) . ' ' . strtoupper($request->last_name),
                'email' => $validatedData['email'],
                'password' => bcrypt($request->password),
                'status_akun' => $tidak_aktif,
                'status_pelamar' => $employee ? $employee->status_resign : null,
                'tanggal_resign' => $employee ? $employee->tgl_resign : null,
                'ket_resign' => $employee ? $employee->alasan_resign : null,
                'role' => "user",
                'area_kerja' => $employee ? $employee->area_kerja : null,
                'email_verifikasi_token' => md5($validatedData['no_ktp'] . now())
            ]);

            // Kirim email verifikasi
            Mail::to($request->email)->send(new SendEmailVerification($user_baru));

            // Buat SP jika ada
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

            DB::commit();

            Alert::success('Berhasil', 'Pendaftaran berhasil! Silakan verifikasi email kamu.');
            return redirect()->route('login');
        } catch (Exception $e) {
            DB::rollBack();

            // Jika user sudah dibuat sebelum error, hapus
            if (isset($user_baru)) {
                $user_baru->delete();
            }

            Log::error('Gagal kirim email verifikasi: ' . $e->getMessage());

            Alert::error('Gagal', 'Terjadi kesalahan saat mengirim email verifikasi. Silakan coba lagi.');
            return redirect()->back();
        }
    }

    public function konfirmasiEmail($id)
    {
        $check = User::where('id', $id)->first();
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
