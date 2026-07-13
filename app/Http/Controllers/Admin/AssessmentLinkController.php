<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAssessmentLinkRequest;
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
            ->with('creator')
            ->withCount('candidates')
            ->latest()
            ->paginate(15);

        return view('admin.assessment-links.index', compact('links'));
    }

    public function create()
    {
        $selectedIds = collect(request()->input('selected_ids', []))
            ->filter(function ($id) {
                return filter_var($id, FILTER_VALIDATE_INT) !== false;
            })
            ->unique()
            ->values();

        $lamarans = Lamaran::query()
            ->with('biodata.user')
            ->whereIn('id', $selectedIds)
            ->get();

        return view('admin.assessment-links.create', compact('lamarans'));
    }

    public function store(StoreAssessmentLinkRequest $request, AssessmentLinkService $assessmentLinkService)
    {
        try {
            $link = $assessmentLinkService->create(
                $request->only(['assessment_type', 'pin', 'fields']),
                $request->input('selected_ids'),
                $request->user()->id
            );
        } catch (ValidationException $exception) {
            Alert::error('Gagal', 'Data link asesmen tidak valid. Silakan periksa kembali isian Anda.');

            return back()->withInput($request->except('pin'));
        } catch (InvalidArgumentException $exception) {
            Alert::error('Gagal', 'Link asesmen tidak dapat dibuat. Silakan periksa kembali isian Anda.');

            return back()->withInput($request->except('pin'));
        }

        $publicUrl = route('assessment-links.public.show', $link->public_token);
        session()->flash('assessment_link_url', $publicUrl);
        Alert::success('Berhasil', 'Link asesmen berhasil dibuat. Salin URL publik yang ditampilkan di halaman daftar.');

        return redirect()->route('assessment-links.index');
    }

    public function show(AssessmentLink $assessmentLink)
    {
        $assessmentLink->load([
            'creator',
            'candidates.lamaran.biodata.user',
            'candidates.audits',
        ]);

        return view('admin.assessment-links.show', ['link' => $assessmentLink]);
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
