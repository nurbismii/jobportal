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
                $request->only(['assessment_type', 'pin', 'fields', 'eligibility_field_id']),
                $request->input('selected_ids'),
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
}
