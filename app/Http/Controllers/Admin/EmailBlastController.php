<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailBlastLog;
use App\Models\HrEmailDelivery;
use App\Models\User;
use App\Jobs\SendHrEmail;
use App\Mail\HrBlastEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EmailBlastController extends Controller
{
    public function create()
    {
        $deliveries = HrEmailDelivery::latest('id')->paginate(25);

        return view('admin.email-blast-log.create', compact('deliveries'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'recipients' => ['required', 'string', 'max:20000'],
            'subject' => ['required', 'string', 'max:200', 'not_regex:/[\r\n]/'],
            'message' => ['required', 'string', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['required', 'file', 'max:5120',
                'mimes:pdf,xls,xlsx,csv,doc,docx,ppt,pptx,txt,jpg,jpeg,png,gif,webp',
                'extensions:pdf,xls,xlsx,csv,doc,docx,ppt,pptx,txt,jpg,jpeg,png,gif,webp',
                function ($attribute, $file, $fail) {
                    $extension = strtolower($file->getClientOriginalExtension());
                    $expected = match ($extension) {
                        'jpg', 'jpeg' => ['jpg', 'jpeg'],
                        'csv', 'txt' => ['csv', 'txt'],
                        default => [$extension],
                    };
                    if (! in_array($file->guessExtension(), $expected, true)) {
                        $fail('Jenis isi lampiran tidak sesuai dengan ekstensi file.');
                    }
                }],
        ]);
        $files = $request->file('attachments', []);
        if (array_sum(array_map(fn ($file) => $file->getSize(), $files)) > 10 * 1024 * 1024) {
            throw ValidationException::withMessages(['attachments' => 'Total lampiran maksimal 10 MB.']);
        }
        $data['message'] = HrBlastEmail::sanitizeMessage($data['message']);
        $text = html_entity_decode(strip_tags($data['message']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_match('/^[\s\p{Z}\x{200B}]*$/u', $text)) {
            throw ValidationException::withMessages(['message' => 'Pesan tidak boleh kosong.']);
        }
        $emails = array_values(array_unique(array_map('strtolower', preg_split('/[\s,;]+/', trim($data['recipients']), -1, PREG_SPLIT_NO_EMPTY))));
        Validator::make(['emails' => $emails], [
            'emails' => ['required', 'array', 'max:200'],
            'emails.*' => ['required', 'email:rfc', 'max:254'],
        ], [
            'emails.max' => 'Maksimal 200 penerima per pengiriman.',
            'emails.*.email' => 'Alamat :input tidak valid. Gunakan alamat email tanpa nama penerima.',
        ])->validate();

        $users = User::whereIn(DB::raw('LOWER(email)'), $emails)
            ->where('role', 'user')->where('status_akun', 1)
            ->whereNotNull('email_verified_at')->get(['id', 'email'])
            ->keyBy(fn ($user) => strtolower($user->email));
        $rejected = array_diff($emails, $users->keys()->all());
        if ($rejected) {
            throw ValidationException::withMessages([
                'recipients' => 'Tidak terdaftar sebagai pengguna aktif terverifikasi: '.implode(', ', $rejected).'. Tidak ada email yang dikirim.',
            ]);
        }

        $attachments = [];
        try {
            foreach ($files as $file) {
                $path = $file->store('', 'hr_email_private');
                $attachments[] = [
                    'path' => $path,
                    'name' => mb_substr(preg_replace('/[\x00-\x1F\x7F]/', '', basename(str_replace('\\', '/', $file->getClientOriginalName()))), 0, 180),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
            DB::transaction(function () use ($users, $data, $request, $attachments) {
                foreach ($users->values() as $index => $user) {
                    $delivery = HrEmailDelivery::create([
                        'user_id' => $user->id, 'sent_by' => $request->user()->id,
                        'recipient' => $user->email, 'subject' => trim($data['subject']),
                        'message' => trim($data['message']), 'status' => 'pending',
                        'attachments' => $attachments ?: null,
                    ]);
                    // Database queue shares the transaction: logs and jobs commit together.
                    SendHrEmail::dispatch($delivery->id)->onConnection('database')
                        ->delay(now()->addSeconds($index * 35));
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('hr_email_private')->delete(array_column($attachments, 'path'));
            report($exception);

            return back()->withInput($request->only('recipients', 'subject', 'message'))
                ->withErrors(['attachments' => 'Gagal menyimpan lampiran atau antrean. Tidak ada email yang diproses. Silakan coba lagi.']);
        }

        return redirect()->route('email-blast-log.create')
            ->with('success', count($users).' email masuk antrean. Pantau hasil pengiriman di riwayat.');
    }

    public function index()
    {
        $datas = EmailBlastLog::with('user')->orderBy('created_at', 'desc')->get();

        return view('admin.email-blast-log.index', compact('datas'));
    }
}
