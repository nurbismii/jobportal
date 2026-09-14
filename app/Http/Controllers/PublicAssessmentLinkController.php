<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicAssessmentResultRequest;
use App\Models\AssessmentLink;
use App\Models\AssessmentLinkCandidate;
use App\Services\AssessmentLinkService;
use Illuminate\Http\Request;

class PublicAssessmentLinkController extends Controller
{
    public function show(string $token)
    {
        $link = $this->accessibleLink($token);

        if (!session()->has($this->accessKey($link))) {
            return view('public-assessment-links.pin', compact('link'));
        }

        if ($link->isDocumentOnly()) {
            return redirect()->route('assessment-documents.index', $link->public_token);
        }

        return view('public-assessment-links.form', compact('link'));
    }

    public function unlock(Request $request, string $token, AssessmentLinkService $assessmentLinkService)
    {
        $link = $this->accessibleLink($token);
        $validated = $request->validate(['pin' => ['required', 'string']]);

        if (!$assessmentLinkService->verifyPin($link, $validated['pin'])) {
            return back()->withErrors(['pin' => 'PIN tidak valid.'])->withInput($request->except('pin'));
        }

        session()->put($this->accessKey($link), true);

        return redirect()->route('assessment-links.public.show', $link->public_token);
    }

    public function storeResult(PublicAssessmentResultRequest $request, string $token, int $candidate, AssessmentLinkService $assessmentLinkService)
    {
        /** @var AssessmentLink $link */
        $link = $request->attributes->get('publicAssessmentLink');

        $linkCandidate = AssessmentLinkCandidate::query()
            ->where('assessment_link_id', $link->id)
            ->findOrFail($candidate);

        $assessmentLinkService->saveResult(
            $linkCandidate,
            $request->input('values'),
            $request->input('petugas_note'),
            $request
        );

        return redirect()->route('assessment-links.public.show', $link->public_token)
            ->with('success', 'Hasil penilaian berhasil disimpan.');
    }

    public function candidates(Request $request, string $token)
    {
        /** @var AssessmentLink $link */
        $link = $request->attributes->get('publicAssessmentLink');
        $query = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');

        abort_unless(in_array($status, ['all', 'pending', 'completed'], true), 422);

        $candidates = AssessmentLinkCandidate::query()
            ->where('assessment_link_id', $link->id)
            ->with('lamaran.biodata.user')
            ->when($query !== '', function ($builder) use ($query) {
                $builder->whereHas('lamaran', function ($lamaranQuery) use ($query) {
                    $lamaranQuery->whereHas('biodata', function ($biodataQuery) use ($query) {
                        $biodataQuery->where('no_ktp', 'like', '%'.$query.'%')
                            ->orWhereHas('user', function ($userQuery) use ($query) {
                                $userQuery->where('name', 'like', '%'.$query.'%');
                            });
                    });
                });
            })
            ->when($status === 'pending', function ($builder) {
                $builder->whereNull('last_submitted_at');
            })
            ->when($status === 'completed', function ($builder) {
                $builder->whereNotNull('last_submitted_at');
            })
            ->orderBy('id')
            ->paginate(25);

        $candidates->setCollection($candidates->getCollection()->map(function (AssessmentLinkCandidate $candidate) {
            $biodata = optional(optional($candidate->lamaran)->biodata);
            $submittedAt = $candidate->last_submitted_at;

            return [
                'id' => $candidate->id,
                'name' => optional($biodata->user)->name,
                'no_ktp' => $biodata->no_ktp,
                'result_values' => $candidate->result_values ?? [],
                'petugas_note' => $candidate->petugas_note,
                'last_submitted_at' => $submittedAt ? $submittedAt->timezone('Asia/Makassar')->toIso8601String() : null,
            ];
        })->values());

        return response()->json($candidates);
    }

    public function autosave(PublicAssessmentResultRequest $request, string $token, int $candidate, AssessmentLinkService $assessmentLinkService)
    {
        /** @var AssessmentLink $link */
        $link = $request->attributes->get('publicAssessmentLink');
        $linkCandidate = AssessmentLinkCandidate::query()
            ->where('assessment_link_id', $link->id)
            ->findOrFail($candidate);

        $savedCandidate = $assessmentLinkService->saveResult(
            $linkCandidate,
            $request->input('values'),
            $request->input('petugas_note'),
            $request
        );

        return response()->json([
            'saved_at' => $savedCandidate->last_submitted_at->timezone('Asia/Makassar')->toIso8601String(),
            'result_values' => $savedCandidate->result_values ?? [],
            'petugas_note' => $savedCandidate->petugas_note,
        ]);
    }

    private function accessibleLink(string $token): AssessmentLink
    {
        $link = AssessmentLink::query()->where('public_token', $token)->firstOrFail();
        abort_unless($link->isAccessibleAt(now('Asia/Makassar')), 404);

        return $link;
    }

    private function accessKey(AssessmentLink $link): string
    {
        return 'assessment_link_access.'.$link->id;
    }
}
