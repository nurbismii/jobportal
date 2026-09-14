<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAssessmentLinkRequest;
use App\Http\Requests\Admin\AddAssessmentLinkCandidatesRequest;
use App\Models\AssessmentLink;
use App\Models\Lamaran;
use App\Services\AssessmentLinkService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RealRashid\SweetAlert\Facades\Alert;

class AssessmentLinkController extends Controller
{
    public function index()
    {
        $links = AssessmentLink::query()
            ->with(['creator', 'candidates.lamaran.lowongan'])
            ->withCount('candidates')
            ->latest()
            ->paginate(15);

        return view('admin.assessment-links.index', compact('links'));
    }

    public function create()
    {
        $selectedIds = collect(old('selected_ids', []))->map(fn ($id) => (int) $id)->all();

        $eligibleLamarans = Lamaran::query()
            ->with(['biodata.user', 'lowongan'])
            ->whereIn('status_proses', ['Tes Kesehatan', 'Tes Lapangan'])
            ->get();

        $lowonganOptions = $eligibleLamarans
            ->map(fn (Lamaran $lamaran) => $lamaran->lowongan)
            ->filter()
            ->unique('id')
            ->sortBy('nama_lowongan')
            ->values();

        return view('admin.assessment-links.create', compact('eligibleLamarans', 'selectedIds', 'lowonganOptions'));
    }

    public function store(StoreAssessmentLinkRequest $request, AssessmentLinkService $assessmentLinkService)
    {
        try {
            $link = $assessmentLinkService->create(
                $request->safe()->only(['assessment_type', 'pin', 'fields', 'eligibility_field_id', 'expires_on']),
                $request->validated('selected_ids', []),
                $request->user()->id
            );
        } catch (ValidationException $exception) {
            Alert::error('Gagal', 'Data link asesmen tidak valid. Silakan periksa kembali isian Anda.');

            return back()->withErrors($exception->errors())->withInput($request->except('pin'));
        } catch (InvalidArgumentException $exception) {
            Alert::error('Gagal', 'Link asesmen tidak dapat dibuat. Silakan periksa kembali isian Anda.');

            return back()->withInput($request->except('pin'));
        }

        $publicUrl = route('assessment-links.public.show', $link->public_token);
        session()->flash('assessment_link_url', $publicUrl);
        Alert::success('Berhasil', 'Link asesmen berhasil dibuat. Salin URL publik yang ditampilkan di halaman daftar.');

        return redirect()->route('assessment-links.index');
    }

    public function show(AssessmentLink $assessmentLink, AssessmentLinkService $assessmentLinkService)
    {
        if ($assessmentLink->isDocumentOnly()) {
            $assessmentLink->load('creator');

            return view('admin.assessment-links.show', ['link' => $assessmentLink]);
        }
        $assessmentLink->load([
            'creator',
            'candidates.lamaran.biodata.user',
            'candidates.lamaran.lowongan',
            'candidates.audits',
        ]);

        $eligibility = trim((string) request('eligibility', 'all'));
        if (!in_array($eligibility, ['all', 'eligible', 'ineligible', 'pending'], true)) {
            $eligibility = 'all';
        }
        $assessmentLink->candidates->each(function ($candidate) use ($assessmentLink, $assessmentLinkService) {
            $candidate->setRelation('assessmentLink', $assessmentLink);
            $candidate->eligibility_status = $assessmentLinkService->eligibilityStatus($candidate);
        });
        if ($eligibility !== 'all') {
            $assessmentLink->setRelation('candidates', $assessmentLink->candidates
                ->filter(fn ($candidate) => $candidate->eligibility_status === $eligibility)
                ->values());
        }

        $candidateSearch = trim((string) request('candidate_search'));
        $eligibleLamarans = Lamaran::query()
            ->with(['biodata.user', 'lowongan'])
            ->where('status_proses', $assessmentLinkService->requiredLamaranStatus($assessmentLink))
            ->whereNotIn('id', $assessmentLink->candidates->pluck('lamaran_id'))
            ->when($candidateSearch !== '', function ($query) use ($candidateSearch) {
                $query->whereHas('biodata', function ($biodataQuery) use ($candidateSearch) {
                    $biodataQuery->where('no_ktp', 'like', "%{$candidateSearch}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$candidateSearch}%"));
                });
            })
            ->latest()
            ->paginate(10, ['*'], 'eligible_page')
            ->withQueryString();

        return view('admin.assessment-links.show', compact('eligibleLamarans', 'candidateSearch', 'eligibility') + ['link' => $assessmentLink]);
    }

    public function storeCandidates(AddAssessmentLinkCandidatesRequest $request, AssessmentLink $assessmentLink, AssessmentLinkService $assessmentLinkService)
    {
        try {
            $assessmentLinkService->addCandidates($assessmentLink, $request->input('lamaran_ids'));
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        Alert::success('Berhasil', 'Kandidat berhasil ditambahkan ke link asesmen.');

        return redirect()->route('assessment-links.show', $assessmentLink);
    }

    public function deactivate(AssessmentLink $assessmentLink)
    {
        if ($assessmentLink->is_active) {
            $assessmentLink->update([
                'is_active' => false,
                'deactivated_at' => Carbon::now('Asia/Makassar'),
                'deactivated_by' => auth()->id(),
            ]);

            Alert::success('Berhasil', 'Link asesmen dinonaktifkan.');
        } else {
            Alert::warning('Tidak ada perubahan', 'Link asesmen sudah tidak aktif.');
        }

        return redirect()->route('assessment-links.show', $assessmentLink);
    }

    public function revealPin(AssessmentLink $assessmentLink)
    {
        $pin = $assessmentLink->pin_encrypted;
        return response()->json(
            $pin === null ? ['message' => 'PIN link lama tidak tersedia karena hanya disimpan sebagai hash.'] : ['pin' => $pin],
            $pin === null ? 404 : 200,
            ['Cache-Control' => 'private, no-store', 'Pragma' => 'no-cache']
        );
    }

    public function updatePin(\App\Http\Requests\Admin\UpdateAssessmentLinkPinRequest $request, AssessmentLink $assessmentLink)
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $assessmentLink) {
            $link = AssessmentLink::whereKey($assessmentLink->id)->lockForUpdate()->firstOrFail();
            $link->update([
                'pin_hash' => \Illuminate\Support\Facades\Hash::make($request->validated('pin')),
                'pin_encrypted' => $request->validated('pin'),
                'pin_version' => (int) $link->pin_version + 1,
            ]);
        });
        Alert::success('Berhasil', 'PIN diperbarui. Klinik perlu membuka link dan memasukkan PIN baru.');

        return redirect()->route('assessment-links.show', $assessmentLink);
    }

    public function extendExpiry(\Illuminate\Http\Request $request, AssessmentLink $assessmentLink)
    {
        abort_unless($assessmentLink->isDocumentOnly() && $assessmentLink->is_active, 403);
        $data = $request->validate(['expires_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:today']]);
        $expiry = Carbon::createFromFormat('!Y-m-d', $data['expires_on'], 'Asia/Makassar')->endOfDay();
        if ($assessmentLink->expires_at && $expiry->lte($assessmentLink->expires_at)) {
            return back()->withErrors(['expires_on' => 'Tanggal baru harus melewati masa berlaku sebelumnya.']);
        }
        $assessmentLink->update(['expires_at' => $expiry]);
        Alert::success('Berhasil', 'Masa berlaku diperpanjang. URL dan PIN tetap sama.');

        return redirect()->route('assessment-links.show', $assessmentLink);
    }
}
