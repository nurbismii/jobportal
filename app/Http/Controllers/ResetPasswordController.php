<?php

namespace App\Http\Controllers;

use App\Mail\EmailResetPassword;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Str;


class ResetPasswordController extends Controller
{
    public function index()
    {
        return view('user.reset-password.index');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $user = User::where('email', $validatedData['email'])->first();

        if (!$user) {
            // Jangan bocorkan apakah email ada atau tidak
            Alert::error('Error', 'Jika email terdaftar, kamu akan menerima tautan reset');
            return back();
        }

        $existingReset = DB::table('password_resets')
            ->orderBy('created_at', 'desc')
            ->where('email', $user->email)
            ->first();

        if ($existingReset) {
            $createdAt = Carbon::parse($existingReset->created_at);
            if (Carbon::now()->diffInMinutes($createdAt) < 60) {
                Alert::warning('Opps!', 'Permintaan reset sudah dikirim. Silakan cek email kamu atau tunggu 1 jam.');
                return back();
            }
        }

        // Generate token baru dan simpan
        $token = Str::random(32);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => $token,
                'created_at' => Carbon::now(),
            ]
        );

        $user->update([
            'email_verifikasi_token' => $token,
        ]);

        Mail::to($user->email)->send(new EmailResetPassword($user));

        Alert::success('Berhasil', 'Silakan cek email kamu untuk melanjutkan proses reset kata sandi');
        return redirect()->route('login');
    }


    public function resetPassword($token)
    {
        $user = User::where('email_verifikasi_token', $token)->first();

        if ($user) {
            return view('user.reset-password.edit', compact('user'));
        }

        Alert::error('Opps!', 'Terjadi kesalahan, saat melakukan permintaan');
        return redirect()->route('login');
    }

    public function update(Request $request, $token)
    {
        if ($request->password !== $request->password_confirmation) {
            Alert::error('Gagal', 'Konfirmasi password tidak sesuai!');
            return redirect()->back();
        }

        User::where('email_verifikasi_token', $token)->update([
            'password' => bcrypt($request->password)
        ]);

        Alert::success('Berhasil', 'Reset kata sandi berhasil, silakan login!');
        return redirect()->route('login');
    }
}
