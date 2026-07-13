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

        $link->load('candidates.lamaran.biodata');

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
