<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerification;
use App\Services\FallbackMailService;
use App\Models\Hris\Peringatan;
use App\Models\SuratPeringatan;
use App\Models\User;
use App\Services\Ocr\KtpIdentityValidator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PendaftaranController extends Controller
{
    private const UNVERIFIED_ACCOUNT_TTL_HOURS = 1;
    private const VERIFICATION_RESEND_COOLDOWN_MINUTES = 15;
    private const VERIFICATION_RESEND_DAILY_LIMIT = 2;

    public function index()
    {
        $title = 'Delete Data!';
        $text = "Are you sure you want to delete?";
        confirmDelete($title, $text);

        return view('user.pendaftaran.index');
    }

    public function verificationNotice(Request $request)
    {
        return view('auth.verification-pending', [
            'email' => $request->query('email', old('email', session('verification_email'))),
            'graceHours' => self::UNVERIFIED_ACCOUNT_TTL_HOURS,
            'resendCooldownMinutes' => self::VERIFICATION_RESEND_COOLDOWN_MINUTES,
            'resendDailyLimit' => self::VERIFICATION_RESEND_DAILY_LIMIT,
        ]);
    }

    public function store(Request $request)
    {
        // Normalize KTP (hindari spasi & mismatch)
        $request->merge([
            'no_ktp' => preg_replace('/\D+/', '', (string) $request->no_ktp)
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

        $identityValidator = app(KtpIdentityValidator::class);

        if (! $identityValidator->isPlausibleNik($validatedData['no_ktp'])) {
            throw ValidationException::withMessages([
                'no_ktp' => 'Nomor KTP tidak valid. Pastikan NIK terdiri dari 16 digit dan sesuai format KTP Indonesia.',
            ]);
        }

        try {
            DB::beginTransaction();
            $now = now();
            $supportsVerificationResendTracking = User::supportsVerificationResendTracking();

            // Cari employee (jika ada)
            $employee = User::latestHrisEmployeeByNoKtp($validatedData['no_ktp']);

            if ($employee && ! $identityValidator->namesMatch($validatedData['name'], (string) $employee->nama_karyawan, 78)) {
                throw ValidationException::withMessages([
                    'name' => 'Nama tidak sesuai dengan riwayat karyawan di HRIS untuk nomor KTP ini.',
                ]);
            }

            $employmentAttributes = User::employmentAttributesFromHrisEmployee($employee);

            $userData = [
                'no_ktp' => $validatedData['no_ktp'],
                'name' => strtoupper($request->name),
                'email' => $validatedData['email'],
                'password' => bcrypt($request->password),
                'status_akun' => 0,
                'role' => 'user',
                'email_verifikasi_token' => Str::random(64),
                'employment_lock_active' => $employmentAttributes['employment_lock_active'],
                'last_hris_sync_at' => $now,
                'status_pelamar' => $employmentAttributes['status_pelamar'],
                'tanggal_resign' => $employmentAttributes['tanggal_resign'],
                'ket_resign' => $employmentAttributes['ket_resign'],
                'area_kerja' => $employmentAttributes['area_kerja'],
            ];

            if ($supportsVerificationResendTracking) {
                $userData['verification_email_last_sent_at'] = $now;
                $userData['verification_resend_count'] = 0;
                $userData['verification_resend_count_date'] = $now->toDateString();
            }

            $userBaru = User::create($userData);

            // Copy SP jika ada
            if ($employee) {
                $peringatan = Peringatan::where('nik_karyawan', $employee->nik)->get();

                foreach ($peringatan as $data) {
                    SuratPeringatan::create([
                        'user_id' => $userBaru->id,
                        'level_sp' => $data->level_sp,
                        'ket_sp' => $data->keterangan,
                        'tanggal_mulai_sp' => $data->tgl_mulai,
                        'tanggal_berakhir_sp' => $data->tgl_berakhir,
                    ]);
                }
            }

            // Commit hanya jika email verifikasi berhasil dikirim.
            app(FallbackMailService::class)->send($validatedData['email'], new EmailVerification($userBaru));

            DB::commit();

            Alert::success('Pendaftaran berhasil', 'Kami sudah mengirim email verifikasi. Verifikasi akun Anda dalam 1 jam agar akun tidak terhapus otomatis.');
            return redirect()->route('verification.notice.public', ['email' => $validatedData['email']]);
        } catch (ValidationException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            // Handle duplicate entry (MySQL error 1062)
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
                Alert::error('Gagal', 'Nomor KTP atau Email sudah terdaftar.');
                return back()->withInput($request->except(['password', 'password_confirmation']));
            }

            Log::error('Register Query Error: ' . $e->getMessage(), [
                'email' => $validatedData['email'] ?? null,
                'no_ktp' => $validatedData['no_ktp'] ?? null,
            ]);

            Alert::error('Gagal', 'Pendaftaran gagal diproses. Data akun tidak disimpan, silakan coba lagi.');
            return back()->withInput($request->except(['password', 'password_confirmation']));
        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Register Error: ' . $e->getMessage());

            Alert::error('Gagal', 'Pendaftaran gagal diproses. Data akun tidak disimpan, silakan coba lagi.');
            return back()->withInput($request->except(['password', 'password_confirmation']));
        }
    }

    public function konfirmasiEmail($id)
    {
        Alert::warning('Tautan Lama Tidak Berlaku', 'Silakan gunakan tautan verifikasi terbaru yang lengkap dari email Anda.');
        return redirect()->route('verification.notice.public');
    }

    public function konfirmasiEmailToken($token)
    {
        $check = User::where('email_verifikasi_token', $token)->first();

        if (!$check) {
            Alert::error('Tautan tidak valid', 'Tautan verifikasi tidak valid atau sudah kedaluwarsa. Jika akun masih menunggu verifikasi, kirim ulang email verifikasi. Jika sudah lewat 1 jam, silakan daftar ulang.');
            return redirect()->route('verification.notice.public');
        }

        if ($check->status_akun == 1) {
            Alert::success('Akun sudah aktif', 'Email Anda sudah terverifikasi. Silakan login.');
            return redirect()->route('login');
        }

        User::where('email_verifikasi_token', $token)->update([
            'email_verified_at' => Carbon::now(),
            'status_akun' => 1
        ]);

        return view('verifikasi-berhasil');
    }

    public function resendVerificationEmail(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|email|max:255',
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.max' => 'Panjang alamat email maksimal 255 karakter.',
        ]);

        try {
            $user = User::where('email', $validatedData['email'])
                ->where('role', 'user')
                ->where('status_akun', 0)
                ->whereNull('email_verified_at')
                ->first();
            $supportsVerificationResendTracking = User::supportsVerificationResendTracking();

            if (! $user) {
                Alert::success('Permintaan diterima', 'Jika akun masih menunggu verifikasi, email verifikasi terbaru telah dikirim.');
                return redirect()->route('verification.notice.public', ['email' => $validatedData['email']]);
            }

            $now = now();
            $lastSentAt = $supportsVerificationResendTracking
                ? ($user->verification_email_last_sent_at ?? $user->created_at)
                : $user->created_at;
            $currentResendCount = $supportsVerificationResendTracking
                ? $this->currentVerificationResendCount($user, $now)
                : 0;

            if ($supportsVerificationResendTracking && $currentResendCount >= self::VERIFICATION_RESEND_DAILY_LIMIT) {
                Alert::warning(
                    'Batas harian tercapai',
                    'Email verifikasi hanya bisa dikirim ulang maksimal ' . self::VERIFICATION_RESEND_DAILY_LIMIT . ' kali dalam sehari. Silakan coba lagi besok.'
                );

                return redirect()->route('verification.notice.public', ['email' => $validatedData['email']]);
            }

            if ($supportsVerificationResendTracking && $lastSentAt && $lastSentAt->copy()->addMinutes(self::VERIFICATION_RESEND_COOLDOWN_MINUTES)->isFuture()) {
                $availableAt = $lastSentAt->copy()->addMinutes(self::VERIFICATION_RESEND_COOLDOWN_MINUTES);
                $remainingMinutes = max(1, $now->diffInMinutes($availableAt));

                Alert::warning(
                    'Tunggu sebentar',
                    'Email verifikasi hanya bisa dikirim ulang setiap ' . self::VERIFICATION_RESEND_COOLDOWN_MINUTES . ' menit. Silakan coba lagi dalam ' . $remainingMinutes . ' menit.'
                );

                return redirect()->route('verification.notice.public', ['email' => $validatedData['email']]);
            }

            DB::transaction(function () use ($user, $now, $currentResendCount, $supportsVerificationResendTracking) {
                $payload = [
                    'email_verifikasi_token' => Str::random(64),
                ];

                if ($supportsVerificationResendTracking) {
                    $payload['verification_email_last_sent_at'] = $now;
                    $payload['verification_resend_count'] = $currentResendCount + 1;
                    $payload['verification_resend_count_date'] = $now->toDateString();
                } else {
                    Log::warning('Verification resend tracking columns are missing. Resend limit is temporarily bypassed until migration runs.');
                }

                $user->forceFill($payload)->save();

                app(FallbackMailService::class)->send($user->email, new EmailVerification($user));
            });

            if ($supportsVerificationResendTracking) {
                $remainingResend = max(0, self::VERIFICATION_RESEND_DAILY_LIMIT - ($currentResendCount + 1));

                Alert::success(
                    'Email terkirim',
                    'Email verifikasi terbaru telah dikirim. Sisa kirim ulang untuk hari ini: ' . $remainingResend . ' kali.'
                );
            } else {
                Alert::success('Email terkirim', 'Email verifikasi terbaru telah dikirim.');
            }
        } catch (\Throwable $e) {
            Log::error('Resend verification email error: ' . $e->getMessage(), [
                'email' => $validatedData['email'],
            ]);

            Alert::success('Permintaan diterima', 'Jika akun masih menunggu verifikasi, email verifikasi terbaru akan segera dikirim.');
        }

        return redirect()->route('verification.notice.public', ['email' => $validatedData['email']]);
    }

    private function currentVerificationResendCount(User $user, Carbon $now): int
    {
        $countDate = $user->verification_resend_count_date;

        if (! $countDate || ! $countDate->isSameDay($now)) {
            return 0;
        }

        return (int) $user->verification_resend_count;
    }
}
