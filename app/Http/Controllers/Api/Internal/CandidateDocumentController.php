<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Internal\CandidateDocumentsRequest;
use App\Services\CandidateDocumentService;

class CandidateDocumentController extends Controller
{
    public function index(CandidateDocumentsRequest $request, CandidateDocumentService $documents)
    {
        return response()->json(
            $documents->findByNoKtp($request->validated()['no_ktp'])
        );
    }

    public function preview(CandidateDocumentService $documents, $no_ktp, $type)
    {
        return $documents->preview($no_ktp, $type);
    }

    public function download(CandidateDocumentService $documents, $no_ktp, $type)
    {
        return $documents->download($no_ktp, $type);
    }
}
