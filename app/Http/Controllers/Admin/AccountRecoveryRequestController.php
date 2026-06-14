<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\EmailApprovedRecoveryAccount;
use App\Services\FallbackMailService;
use App\Models\AccountRecoveryRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        $result = $this->approveRequest($requestItem);

        if (! $result['success']) {
            Alert::error('Gagal', $result['message']);
            return back();
        }

        Alert::success('Berhasil', 'Request lupa akun disetujui dan akun baru telah dikirim ke email terbaru.');
        return back();
    }

    public function reject($id)
    {
        $requestItem = AccountRecoveryRequest::findOrFail($id);
        $result = $this->rejectRequest($requestItem);

        if (! $result['success']) {
            Alert::error('Gagal', $result['message']);
            return back();
        }

        Alert::success('Berhasil', 'Request lupa akun telah ditolak.');
        return back();
    }

    public function bulkAction(Request $request)
    {
        $validatedData = $request->validate([
            'action' => 'required|in:approve,reject',
            'request_ids' => 'required|array|min:1',
            'request_ids.*' => 'integer|distinct|exists:account_recovery_requests,id',
        ], [
            'action.required' => 'Pilih aksi bulk terlebih dahulu.',
            'action.in' => 'Aksi bulk tidak valid.',
            'request_ids.required' => 'Pilih minimal satu request.',
            'request_ids.array' => 'Format pilihan request tidak valid.',
            'request_ids.min' => 'Pilih minimal satu request.',
            'request_ids.*.exists' => 'Ada request yang tidak ditemukan.',
        ]);

        $requestItems = AccountRecoveryRequest::with('user')
            ->whereIn('id', $validatedData['request_ids'])
            ->get()
            ->keyBy('id');

        $successCount = 0;
        $failedMessages = [];

        foreach ($validatedData['request_ids'] as $requestId) {
            $requestItem = $requestItems->get($requestId);

            if (! $requestItem) {
                $failedMessages[] = '#' . $requestId . ': request tidak ditemukan.';
                continue;
            }

            $result = $validatedData['action'] === 'approve'
                ? $this->approveRequest($requestItem)
                : $this->rejectRequest($requestItem);

            if ($result['success']) {
                $successCount++;
                continue;
            }

            $failedMessages[] = '#' . $requestItem->id . ': ' . $result['message'];
        }

        $actionLabel = $validatedData['action'] === 'approve' ? 'approve' : 'reject';

        if (empty($failedMessages)) {
            Alert::success('Berhasil', $successCount . ' request berhasil di-' . $actionLabel . '.');
            return back();
        }

        $failedSummary = implode(' | ', array_slice($failedMessages, 0, 3));

        if (count($failedMessages) > 3) {
            $failedSummary .= ' | dan ' . (count($failedMessages) - 3) . ' request lain.';
        }

        if ($successCount > 0) {
            Alert::warning(
                'Sebagian berhasil',
                $successCount . ' request berhasil di-' . $actionLabel . '. Gagal: ' . $failedSummary
            );
        } else {
            Alert::error('Gagal', 'Tidak ada request yang berhasil diproses. ' . $failedSummary);
        }

        return back();
    }

    private function approveRequest(AccountRecoveryRequest $requestItem): array
    {
        if ($requestItem->status !== 'pending') {
            return [
                'success' => false,
                'message' => 'Request ini sudah diproses sebelumnya.',
            ];
        }

        $user = $requestItem->user;

        if (! $user) {
            return [
                'success' => false,
                'message' => 'Akun yang terkait dengan request ini tidak ditemukan.',
            ];
        }

        if (! $this->hasVerifiedOldEmail($user)) {
            return [
                'success' => false,
                'message' => 'Email lama akun ini belum terverifikasi, sehingga request tidak boleh di-approve.',
            ];
        }

        $emailBaru = $requestItem->requested_email;

        if (blank($emailBaru)) {
            return [
                'success' => false,
                'message' => 'Email baru pada request ini kosong.',
            ];
        }

        if (strcasecmp((string) $user->email, (string) $emailBaru) === 0) {
            return [
                'success' => false,
                'message' => 'Email baru sama dengan email lama.',
            ];
        }

        $emailDipakai = User::where('email', $emailBaru)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($emailDipakai) {
            return [
                'success' => false,
                'message' => 'Email baru pada request ini sudah digunakan oleh akun lain.',
            ];
        }

        $passwordBaru = $this->generateRandomPassword();
        $emailLama = $user->email;

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

        return [
            'success' => true,
            'message' => 'Request berhasil di-approve.',
        ];
    }

    private function rejectRequest(AccountRecoveryRequest $requestItem): array
    {
        if ($requestItem->status !== 'pending') {
            return [
                'success' => false,
                'message' => 'Request ini sudah diproses sebelumnya.',
            ];
        }

        $requestItem->update([
            'status' => 'rejected',
            'processed_by' => auth()->id(),
            'processed_at' => Carbon::now(),
            'admin_notes' => 'Request ditolak oleh admin.',
        ]);

        return [
            'success' => true,
            'message' => 'Request berhasil ditolak.',
        ];
    }

    private function hasVerifiedOldEmail(User $user): bool
    {
        return (int) $user->status_akun === 1
            && $user->email_verified_at !== null;
    }

    private function generateRandomPassword(): string
    {
        $letters = Str::random(8);
        $numbers = (string) random_int(100, 999);
        $symbols = ['!', '@', '#', '$'];

        return $letters . $numbers . $symbols[array_rand($symbols)];
    }
}
