<?php

namespace App\Http\Controllers;

use App\Models\Hris\Employee;
use App\Models\Hris\Kelurahan;
use App\Models\Hris\Peringatan;
use App\Models\SuratPeringatan;
use App\Models\User;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

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

        // Check if the user already exists
        if (User::where('no_ktp', $request->no_ktp)->first()) {

            Alert::error('Gagal', 'Nomor KTP sudah terdaftar!');
            return redirect()->back();
        }

        // Check password confirmation
        if ($request->password === $request->password_confirmation) {

            $employee = Employee::where('no_ktp', $validatedData['no_ktp'])->first();

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
            ]);

            // Check if the employee exists
            if ($employee) {

                // Create a new biodata entry
                $peringatan = Peringatan::where('nik_karyawan', $employee->nik)
                    ->get();

                if ($peringatan->count() > 0) {
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
            }

            Alert::success('Berhasil', 'Pendaftaran berhasil! Silakan vefikasi email kamu.');
            return redirect()->route('login');
        } else {

            Alert::error('Gagal', 'Konfirmasi password tidak sesuai!');
            return redirect()->back();
        }
    }
}
