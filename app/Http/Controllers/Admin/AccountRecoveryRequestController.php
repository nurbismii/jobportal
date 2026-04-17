<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\EmailApprovedRecoveryAccount;
use App\Services\FallbackMailService;
use App\Models\AccountRecoveryRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;

class AccountRecoveryRequestController extends Controller
{
    public function index()
    {
        $requests = AccountRecoveryRequest::with(['user', 'processor'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'approved' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $stats = [
            'pending' => AccountRecoveryRequest::where('status', 'pending')->count(),
            'approved' => AccountRecoveryRequest::where('status', 'approved')->count(),
            'rejected' => AccountRecoveryRequest::where('status', 'rejected')->count(),
        ];

        return view('admin.account-recovery-requests.index', compact('requests', 'stats'));
    }

    public function approve($id)
    {
        $requestItem = AccountRecoveryRequest::with('user')->findOrFail($id);

        if ($requestItem->status !== 'pending') {
            Alert::warning('Peringatan', 'Request ini sudah diproses sebelumnya.');
            return back();
        }

        $user = $requestItem->user;

        if (!$user) {
            Alert::error('Gagal', 'Akun yang terkait dengan request ini tidak ditemukan.');
            return back();
        }

        $emailDipakai = User::where('email', $requestItem->requested_email)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($emailDipakai) {
            Alert::error('Gagal', 'Email baru pada request ini sudah digunakan oleh akun lain.');
            return back();
        }

        $passwordBaru = $this->generateRandomPassword();
        $emailLama = $user->email;
        $emailBaru = $requestItem->requested_email;

        DB::transaction(function () use ($requestItem, $user, $passwordBaru, $emailLama, $emailBaru) {
            $user->forceFill([
                'email' => $emailBaru,
                'password' => bcrypt($passwordBaru),
                'email_verified_at' => Carbon::now(),
                'status_akun' => 1,
                'email_verifikasi_token' => Str::random(64),
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('password_resets')
                ->where('email', $emailLama)
                ->delete();

            $requestItem->update([
                'status' => 'approved',
                'processed_by' => auth()->id(),
                'processed_at' => Carbon::now(),
                'approved_email' => $emailBaru,
                'admin_notes' => 'Email akun diganti dan password acak baru telah dikirim ke email terbaru.',
            ]);

            app(FallbackMailService::class)->send($emailBaru, new EmailApprovedRecoveryAccount([
                'name' => $user->name,
                'no_ktp' => $user->no_ktp,
                'email' => $emailBaru,
                'password' => $passwordBaru,
                'login_url' => route('login'),
            ]));
        });

        Alert::success('Berhasil', 'Request lupa akun disetujui dan akun baru telah dikirim ke email terbaru.');
        return back();
    }

    public function reject($id)
    {
        $requestItem = AccountRecoveryRequest::findOrFail($id);

        if ($requestItem->status !== 'pending') {
            Alert::warning('Peringatan', 'Request ini sudah diproses sebelumnya.');
            return back();
        }

        $requestItem->update([
            'status' => 'rejected',
            'processed_by' => auth()->id(),
            'processed_at' => Carbon::now(),
            'admin_notes' => 'Request ditolak oleh admin.',
        ]);

        Alert::success('Berhasil', 'Request lupa akun telah ditolak.');
        return back();
    }

    private function generateRandomPassword()
    {
        $letters = Str::random(8);
        $numbers = (string) random_int(100, 999);
        $symbols = ['!', '@', '#', '$'];

        return $letters . $numbers . $symbols[array_rand($symbols)];
    }
}
