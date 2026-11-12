<?php

namespace App\Http\Controllers;

use App\Models\AssessmentLink;
use App\Services\FieldTestService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FieldTestController extends Controller
{
    public function show(Request $request, string $token, int $candidate)
    {
        $link = $this->link($request);
        $candidate = $link->candidates()->with('lamaran.biodata.user')->findOrFail($candidate);
        $revisionAudit = $candidate->audits()->where('new_values->event', 'revision_requested')->latest('id')->first();
        return response()->view('field-tests.form', compact('link', 'candidate', 'revisionAudit'))->header('Cache-Control', 'private, no-store');
    }

    public function save(Request $request, string $token, int $candidate, FieldTestService $service)
    {
        $input = $request->validate(['version' => 'required|integer|min:1', 'values' => 'present|array', 'petugas_note' => 'nullable|string|max:2000']);
        return response()->json($this->state($service->save($this->link($request), $candidate, $input, $request)));
    }

    public function sign(Request $request, string $token, int $candidate, FieldTestService $service)
    {
        $input = $request->validate($this->signerRules() + ['revision' => 'required|integer|min:1', 'signature_data' => 'required|string|max:270000', 'consent' => 'required|accepted']);
        return response()->json($this->state($service->sign($this->link($request), $candidate, $input, $request)));
    }

    public function revise(Request $request, string $token, int $candidate, FieldTestService $service)
    {
        $input = $request->validate($this->signerRules() + ['reason' => 'required|string|min:5|max:1000']);
        return response()->json($this->state($service->revise($this->link($request), $candidate, $input, $request)));
    }

    public function report(Request $request, string $token, int $candidate)
    {
        $link = $this->link($request);
        $candidate = $link->candidates()->with('lamaran.biodata.user')->findOrFail($candidate);
        return response()->view('field-tests.report', compact('link', 'candidate'))->header('Cache-Control', 'private, no-store');
    }

    private function link(Request $request): AssessmentLink
    {
        $link = $request->attributes->get('publicAssessmentLink');
        abort_unless($link?->isFieldTest(), 404);
        return $link;
    }

    private function signerRules(): array
    {
        return ['version' => 'required|integer|min:1', 'role' => ['required', Rule::in(array_keys(FieldTestService::ROLES))]];
    }

    private function state($candidate): array
    {
        return ['version' => $candidate->field_test_version, 'revision' => $candidate->field_test_revision, 'signed_count' => count($candidate->field_test_signatures ?? []), 'saved_at' => now('Asia/Makassar')->format('H:i')];
    }
}
