<?php

namespace App\Http\Controllers;

use App\Models\AccountRecoveryRequest;
use App\Mail\EmailRecoverAccount;
use App\Mail\EmailRecoveryRequest;
use App\Mail\EmailVerification;
use App\Services\FallbackMailService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Str;


class ResetPasswordController extends Controller
{
    private const RECOVERY_EXPIRE_MINUTES = 60;

    public function index()
    {
        return view('user.reset-password.index');
    }

    public function store(Request $request)
    {
        $request->merge([
            'no_ktp' => preg_replace('/\D+/', '', (string) $request->no_ktp),
        ]);

        $validatedData = $request->validate([
            'no_ktp' => 'required|digits:16',
            'email' => 'required|email|max:255',
        ], [
            'no_ktp.required' => 'Nomor KTP wajib diisi.',
            'no_ktp.digits' => 'Nomor KTP harus terdiri dari 16 digit.',
            'email.required' => 'Email lama wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Panjang email maksimal 255 karakter.',
        ]);

        $user = User::where('no_ktp', $validatedData['no_ktp'])
            ->where('email', $validatedData['email'])
            ->first();

        if (!$user) {
            Alert::success('Permintaan diterima', 'Jika data akun cocok, tautan pemulihan akan dikirim ke email yang terdaftar.');
            return redirect()->route('login');
        }

        if (! $this->hasVerifiedOldEmail($user)) {
            Alert::warning(
                'Email belum diverifikasi',
                'Akun ini belum aktif karena email lama belum diverifikasi. Gunakan kirim ulang email verifikasi, bukan lupa akun.'
            );

            return redirect()->route('verification.notice.public', ['email' => $user->email]);
        }

        $existingReset = DB::table('password_resets')
            ->where('email', $user->email)
            ->where('token', $user->email_verifikasi_token)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($existingReset) {
            $createdAt = Carbon::parse($existingReset->created_at);
            if (Carbon::now()->diffInMinutes($createdAt) < self::RECOVERY_EXPIRE_MINUTES) {
                Alert::warning('Opps!', 'Tautan pemulihan terakhir masih aktif. Silakan cek email lama Anda atau tunggu 1 jam untuk meminta ulang.');
                return back();
            }
        }

        $token = Str::random(64);

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

        app(FallbackMailService::class)->send($user->email, new EmailRecoverAccount($user));

        Alert::success('Berhasil', 'Tautan pemulihan akun telah dikirim ke email lama yang terdaftar.');
        return redirect()->route('login');
    }

    public function submitRecoveryRequest(Request $request)
    {
        $request->merge([
            'no_ktp_manual' => preg_replace('/\D+/', '', (string) $request->no_ktp_manual),
        ]);

        $validatedData = $request->validate([
            'no_ktp_manual' => 'required|digits:16',
            'name_manual' => 'required|string|max:255',
            'email_lama_manual' => 'required|email|max:255',
            'email_baru' => 'required|email|max:255|different:email_lama_manual',
            'no_telp_manual' => 'nullable|string|max:25',
            'keterangan_manual' => 'nullable|string|max:1000',
        ], [
            'no_ktp_manual.required' => 'Nomor KTP wajib diisi.',
            'no_ktp_manual.digits' => 'Nomor KTP harus terdiri dari 16 digit.',
            'name_manual.required' => 'Nama lengkap wajib diisi.',
            'name_manual.max' => 'Nama lengkap maksimal 255 karakter.',
            'email_lama_manual.required' => 'Email lama wajib diisi.',
            'email_lama_manual.email' => 'Format email lama tidak valid.',
            'email_lama_manual.max' => 'Panjang email lama maksimal 255 karakter.',
            'email_baru.required' => 'Email baru wajib diisi.',
            'email_baru.email' => 'Format email baru tidak valid.',
            'email_baru.max' => 'Panjang email baru maksimal 255 karakter.',
            'email_baru.different' => 'Email baru harus berbeda dari email lama.',
            'no_telp_manual.max' => 'Nomor telepon maksimal 25 karakter.',
            'keterangan_manual.max' => 'Keterangan maksimal 1000 karakter.',
        ]);

        $user = User::with('biodata')
            ->where('no_ktp', $validatedData['no_ktp_manual'])
            ->where('email', $validatedData['email_lama_manual'])
            ->first();

        if ($user) {
            if (! $this->hasVerifiedOldEmail($user)) {
                Alert::warning(
                    'Request tidak dapat diproses',
                    'Permintaan ganti email hanya bisa diajukan untuk akun yang email lamanya sudah terverifikasi. Jika akun baru belum verifikasi, gunakan kirim ulang email verifikasi.'
                );

                return redirect()->route('verification.notice.public', ['email' => $validatedData['email_lama_manual']]);
            }

            $emailBaruDipakai = User::where('email', $validatedData['email_baru'])
                ->where('id', '!=', $user->id)
                ->exists();

            if ($emailBaruDipakai) {
                return back()
                    ->withErrors(['email_baru' => 'Email baru sudah digunakan oleh akun lain.'])
                    ->withInput();
            }

            $accountRecoveryRequest = AccountRecoveryRequest::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'status' => 'pending',
                ],
                [
                    'no_ktp' => $validatedData['no_ktp_manual'],
                    'requested_name' => $validatedData['name_manual'],
                    'requested_email' => $validatedData['email_baru'],
                    'requested_phone' => $validatedData['no_telp_manual'] ?? null,
                    'requested_notes' => $validatedData['keterangan_manual'] ?? null,
                    'registered_name' => $user->name,
                    'registered_email' => $user->email,
                    'registered_birth_date' => optional($user->biodata)->tanggal_lahir,
                    'registered_phone' => optional($user->biodata)->no_telp,
                    'processed_by' => null,
                    'processed_at' => null,
                    'approved_email' => null,
                    'admin_notes' => null,
                ]
            );

            app(FallbackMailService::class)->send(config('mail.recovery_recipient.address'), new EmailRecoveryRequest([
                    'request_id' => $accountRecoveryRequest->id,
                    'input_name' => $validatedData['name_manual'],
                    'input_no_ktp' => $validatedData['no_ktp_manual'],
                    'input_old_email' => $validatedData['email_lama_manual'],
                    'input_new_email' => $validatedData['email_baru'],
                    'input_phone' => $validatedData['no_telp_manual'] ?? null,
                    'input_notes' => $validatedData['keterangan_manual'] ?? null,
                    'registered_name' => $user->name,
                    'registered_email' => $user->email,
                    'registered_status_akun' => (int) ($user->status_akun ?? 0),
                    'registered_tanggal_lahir' => optional($user->biodata)->tanggal_lahir,
                    'registered_phone' => optional($user->biodata)->no_telp,
                ]));
        }

        Alert::success(
            'Permintaan diterima',
            'Jika data akun ditemukan, permintaan pemulihan akan diteruskan ke tim HR untuk verifikasi manual.'
        );

        return redirect()->route('lupa-akun.index');
    }


    public function resetPassword($token)
    {
        $recovery = $this->getValidRecovery($token);

        if ($recovery) {
            return view('user.reset-password.edit', [
                'user' => $recovery['user'],
                'token' => $token,
            ]);
        }

        Alert::error('Opps!', 'Tautan pemulihan tidak valid atau sudah kedaluwarsa. Silakan ajukan permintaan baru.');
        return redirect()->route('lupa-akun.index');
    }

    public function update(Request $request, $token)
    {
        $recovery = $this->getValidRecovery($token);

        if (!$recovery) {
            Alert::error('Opps!', 'Tautan pemulihan tidak valid atau sudah kedaluwarsa. Silakan ajukan permintaan baru.');
            return redirect()->route('lupa-akun.index');
        }

        $user = $recovery['user'];

        $validatedData = $request->validate([
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.required' => 'Email baru wajib diisi.',
            'email.email' => 'Format email baru tidak valid.',
            'email.max' => 'Panjang email baru maksimal 255 karakter.',
            'email.unique' => 'Email baru sudah digunakan oleh akun lain.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sesuai.',
        ]);

        $emailLama = $user->email;
        $tokenVerifikasiBaru = Str::random(64);
        $now = Carbon::now();

        $payload = [
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
            'email_verified_at' => null,
            'status_akun' => 0,
            'email_verifikasi_token' => $tokenVerifikasiBaru,
        ];

        if (User::supportsVerificationResendTracking()) {
            $payload['verification_email_last_sent_at'] = $now;
            $payload['verification_resend_count'] = 0;
            $payload['verification_resend_count_date'] = $now->toDateString();
        }

        $user->update($payload);

        DB::table('password_resets')
            ->where('email', $emailLama)
            ->delete();

        app(FallbackMailService::class)->send($validatedData['email'], new EmailVerification($user->fresh()));

        Alert::success('Berhasil', 'Email dan kata sandi berhasil diperbarui. Verifikasi email baru Anda dalam 1 jam sebelum login.');
        return redirect()->route('verification.notice.public', ['email' => $validatedData['email']]);
    }

    private function hasVerifiedOldEmail(User $user): bool
    {
        return (int) $user->status_akun === 1
            && $user->email_verified_at !== null;
    }

    private function getValidRecovery($token)
    {
        $user = User::where('email_verifikasi_token', $token)->first();

        if (!$user) {
            return null;
        }

        $recovery = DB::table('password_resets')
            ->where('email', $user->email)
            ->where('token', $token)
            ->first();

        if (!$recovery) {
            return null;
        }

        if (Carbon::parse($recovery->created_at)->addMinutes(self::RECOVERY_EXPIRE_MINUTES)->isPast()) {
            DB::table('password_resets')
                ->where('email', $user->email)
                ->delete();

            return null;
        }

        return [
            'user' => $user,
            'recovery' => $recovery,
        ];
    }
}
