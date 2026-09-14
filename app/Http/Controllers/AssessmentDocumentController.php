<?php

namespace App\Http\Controllers;

use App\Models\AssessmentDocument;
use App\Models\AssessmentDocumentFolder;
use App\Models\AssessmentLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AssessmentDocumentController extends Controller
{
    public function index(Request $request, string $token)
    {
        return $this->listing($request->attributes->get('publicAssessmentLink'), false);
    }

    public function adminIndex(AssessmentLink $assessmentLink)
    {
        return $this->listing($assessmentLink, true);
    }

    private function listing(AssessmentLink $link, bool $admin)
    {
        $filters = request()->validate([
            'folder_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', ...(request()->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'attendance_search' => ['nullable', 'string', 'max:150'],
            'mcu_detail_search' => ['nullable', 'string', 'max:150'],
            'mcu_recap_search' => ['nullable', 'string', 'max:150'],
        ]);
        $folders = AssessmentDocumentFolder::where('assessment_link_id', $link->id)->orderBy('name')->get();
        $selectedFolder = null;
        if (($admin || $link->isDocumentOnly()) && request()->filled('folder_id')) {
            $selectedFolder = $folders->firstWhere('id', request('folder_id'));
            abort_unless($selectedFolder, 404);
        }
        $stages = AssessmentDocument::stageStatus($link, $selectedFolder?->id);
        $baseQuery = AssessmentDocument::where('assessment_link_id', $link->id)
            ->when($selectedFolder, fn ($query) => $query->where('assessment_document_folder_id', $selectedFolder->id));
        if ($admin && !empty($filters['date_from'])) {
            $baseQuery->where('created_at', '>=', \Illuminate\Support\Carbon::parse($filters['date_from'], 'Asia/Makassar')->startOfDay()->setTimezone(config('app.timezone')));
        }
        if ($admin && !empty($filters['date_to'])) {
            $baseQuery->where('created_at', '<', \Illuminate\Support\Carbon::parse($filters['date_to'], 'Asia/Makassar')->addDay()->startOfDay()->setTimezone(config('app.timezone')));
        }
        $histories = [];
        foreach (AssessmentDocument::CATEGORIES as $category => $label) {
            $search = trim($filters[$category.'_search'] ?? '');
            $query = (clone $baseQuery)->where('category', $category);
            if ($search !== '') {
                $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
                $query->whereRaw("original_name LIKE ? ESCAPE '!'", [$pattern]);
            }
            $histories[$category] = $query->with('folder')->latest('id')
                ->paginate(15, ['*'], $category.'_page')->withQueryString()->fragment('history-'.$category);
        }

        return view('assessment-documents.index', compact('link', 'admin', 'folders', 'histories', 'selectedFolder', 'stages', 'filters'));
    }

    public function storeFolder(Request $request, string $token)
    {
        $link = $request->attributes->get('publicAssessmentLink');
        abort_unless($link->supportsMcuDocuments(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'regex:/^[\pL\pN][\pL\pN ._()-]*$/u',
                Rule::unique('assessment_document_folders')->where('assessment_link_id', $link->id)],
        ]);
        $folder = AssessmentDocumentFolder::create(['assessment_link_id' => $link->id, 'name' => $data['name']]);

        return redirect()->route('assessment-documents.index', ['token' => $link->public_token] + ($link->isDocumentOnly() ? ['folder_id' => $folder->id] : []))
            ->with('success', 'Folder berhasil dibuat. Mulai dengan mengunggah daftar hadir.');
    }

    public function queue(Request $request, string $token)
    {
        $request->validate([
            'upload_id' => ['required', 'uuid'],
            'category' => ['required', Rule::in(['mcu_detail'])],
            'files' => ['required', 'array', 'size:1'],
        ]);

        return $this->store($request, $token);
    }

    public function store(Request $request, string $token)
    {
        $link = $request->attributes->get('publicAssessmentLink');
        $categories = $link->supportsMcuDocuments() ? array_keys(AssessmentDocument::CATEGORIES) : ['attendance'];
        $detail = $request->input('category') === 'mcu_detail';
        $usesFolder = $link->isDocumentOnly() || $detail;
        $data = $request->validate([
            'category' => ['required', Rule::in($categories)],
            'upload_id' => ['nullable', 'uuid', Rule::prohibitedIf(!$detail)],
            'folder_id' => [$usesFolder ? 'required' : 'prohibited', 'integer',
                Rule::exists('assessment_document_folders', 'id')->where('assessment_link_id', $link->id)],
            'files' => ['required', 'array', 'min:1', 'max:'.($detail && !$request->filled('upload_id') ? '10' : '1')],
            'files.*' => ['required', 'file', 'max:10240', $detail ? 'mimes:pdf' : 'mimes:xls,xlsx',
                $detail ? 'extensions:pdf' : 'extensions:xls,xlsx'],
        ]);

        $paths = [];
        try {
            DB::transaction(function () use ($request, $link, $data, $usesFolder, &$paths) {
                // Serialize stage checks and writes for this link; only committed uploads unlock the next stage.
                $lockedLink = AssessmentLink::whereKey($link->id)->lockForUpdate()->firstOrFail();
                abort_unless($lockedLink->isAccessibleAt(now()), 404);
                $stages = AssessmentDocument::stageStatus($link, $usesFolder ? (int) $data['folder_id'] : null);
                if ($data['category'] === 'mcu_detail' && !$stages['attendance']) {
                    throw ValidationException::withMessages(['category' => 'Unggah Daftar Hadir terlebih dahulu pada batch ini.']);
                }
                if ($data['category'] === 'mcu_recap' && (!$stages['attendance'] || !$stages['mcu_detail'])) {
                    throw ValidationException::withMessages(['category' => 'Kirim Daftar Hadir dan Hasil MCU - Detail terlebih dahulu pada batch ini.']);
                }
                foreach ($request->file('files') as $file) {
                    $directory = $link->id.'/'.($usesFolder ? 'folders/'.$data['folder_id'].'/'.$data['category'] : $data['category']);
                    if (!empty($data['upload_id'])) {
                        // A retry after a lost response must not create another copy of the same upload.
                        $name = $data['upload_id'].'-'.hash_file('sha256', $file->getRealPath()).'.pdf';
                        $path = $directory.'/'.$name;
                        $existing = AssessmentDocument::withTrashed()->where('assessment_link_id', $link->id)->where('path', $path)->first();
                        if ($existing?->trashed()) {
                            throw ValidationException::withMessages(['upload_id' => 'Kiriman ini sudah dihapus. Pilih kiriman baru jika ingin mengunggah ulang.']);
                        }
                        if ($existing) {
                            continue;
                        }
                        $path = $file->storeAs($directory, $name, 'assessment_private');
                    } else {
                        $path = $file->store($directory, 'assessment_private');
                    }
                    $paths[] = $path;
                    AssessmentDocument::create([
                        'assessment_link_id' => $link->id,
                        'assessment_document_folder_id' => $usesFolder ? $data['folder_id'] : null,
                        'category' => $data['category'],
                        'original_name' => mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 255),
                        'path' => $path,
                        'size' => $file->getSize(),
                    ]);
                }
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            foreach ($paths as $path) {
                try {
                    Storage::disk('assessment_private')->delete($path);
                } catch (\Throwable $cleanupException) {
                    report($cleanupException);
                }
            }
            report($exception);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'File gagal disimpan. Silakan coba kembali.'], 500);
            }

            return back()->withErrors(['files' => 'File gagal disimpan. Silakan coba kembali.']);
        }

        if ($request->expectsJson()) {
            return response()->json(['saved' => true, 'message' => 'File berhasil disimpan.']);
        }

        return back()->with('success', 'File berhasil dikirim. Kiriman sebelumnya tetap tersimpan.');
    }

    public function download(Request $request, string $token, int $document)
    {
        return $this->downloadForLink($request->attributes->get('publicAssessmentLink'), $document);
    }

    public function destroy(Request $request, string $token, int $document)
    {
        return $this->deleteForLink($request->attributes->get('publicAssessmentLink'), $document, false);
    }

    public function adminDestroy(AssessmentLink $assessmentLink, int $document)
    {
        return $this->deleteForLink($assessmentLink, $document, true);
    }

    private function deleteForLink(AssessmentLink $link, int $id, bool $admin)
    {
        DB::transaction(function () use ($link, $id, $admin) {
            $lockedLink = AssessmentLink::whereKey($link->id)->lockForUpdate()->firstOrFail();
            if (!$admin) abort_unless($lockedLink->isAccessibleAt(now()), 404);
            $document = AssessmentDocument::where('assessment_link_id', $link->id)->findOrFail($id);
            $remaining = AssessmentDocument::where('assessment_link_id', $link->id)
                ->where('id', '!=', $id)
                ->when($link->isDocumentOnly(), fn ($query) => $query->where('assessment_document_folder_id', $document->assessment_document_folder_id))
                ->distinct()->pluck('category')->all();
            if ($document->category === 'attendance' && !in_array('attendance', $remaining, true)
                && (in_array('mcu_detail', $remaining, true) || in_array('mcu_recap', $remaining, true))) {
                throw ValidationException::withMessages(['history' => 'Hapus seluruh rekap dan detail MCU pada batch ini sebelum menghapus daftar hadir terakhir.']);
            }
            if ($document->category === 'mcu_detail' && !in_array('mcu_detail', $remaining, true) && in_array('mcu_recap', $remaining, true)) {
                throw ValidationException::withMessages(['history' => 'Hapus seluruh rekap MCU pada batch ini sebelum menghapus PDF detail terakhir.']);
            }
            $document->forceFill(['deleted_by' => $admin ? auth()->id() : null, 'deleted_via' => $admin ? 'admin' : 'clinic'])->save();
            $document->delete();
        });

        return back()->with('success', 'Riwayat berhasil dihapus. Dokumen tidak lagi tersedia untuk diunduh.');
    }

    public function adminDownload(AssessmentLink $assessmentLink, int $document)
    {
        return $this->downloadForLink($assessmentLink, $document);
    }

    private function downloadForLink(AssessmentLink $link, int $id)
    {
        $document = AssessmentDocument::where('assessment_link_id', $link->id)->findOrFail($id);
        $disk = Storage::disk('assessment_private');
        abort_unless($disk->exists($document->path), 404);

        return $disk->download($document->path, $document->original_name, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
