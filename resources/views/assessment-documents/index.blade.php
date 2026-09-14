<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Dokumen Klinik Rapha</title>
    <style>body{background:#f3f6fa;color:#243348;font-family:Arial,sans-serif}main{max-width:1100px;margin:auto;padding:28px 18px}.panel{background:white;border:1px solid #dbe3ed;border-radius:12px;padding:24px;margin:20px 0}.forms{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px}label{display:block;margin:12px 0 5px}input,select{max-width:100%;padding:9px}button,.action{display:inline-block;background:#174b77;color:white;padding:10px 16px;border:0;border-radius:6px;margin-top:14px;cursor:pointer}button:disabled{opacity:.6}.notice{padding:14px;background:#e6f5ec}.error{background:#fff0ee}.muted{color:#596a7c}table{width:100%;border-collapse:collapse}td,th{text-align:left;padding:12px;border-bottom:1px solid #e3e8ef;overflow-wrap:anywhere}.scroll{overflow-x:auto}h1{font-size:28px}h2{font-size:20px}</style>
    <style>
        .drop-zone{position:relative;display:flex;flex-direction:column;align-items:center;gap:10px;min-height:145px;box-sizing:border-box;padding:24px 16px;border:2px dashed #a7bdd2;border-radius:10px;background:#f7faff;text-align:center;cursor:pointer;transition:background .15s,border-color .15s}
        .drop-zone:hover,.drop-zone:focus-within,.drop-zone.is-dragging{background:#e8f2fc;border-color:#174b77}
        .drop-zone:focus-within{outline:3px solid #90bce5;outline-offset:3px}
        .drop-zone.is-disabled{background:#f1f3f5;border-color:#d3dae2;color:#6b7785;cursor:not-allowed}
        .drop-zone svg{width:30px;height:30px;color:#174b77}
        .drop-zone input[type=file]{position:absolute;width:1px;height:1px;padding:0;opacity:0;overflow:hidden}
        .drop-hint{font-size:13px;font-weight:normal;color:#596a7c}
        .file-selection{font-size:14px;overflow-wrap:anywhere;margin:10px 0}.file-selection ul{max-height:150px;overflow:auto;padding-left:20px}.drop-error{color:#a52828}
        .file-selection li{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid #e3e8ef}.file-selection li span{min-width:0}.remove-selected-file{flex-shrink:0;margin:0;padding:6px 10px;background:#fff1f1;color:#a52828;border:1px solid #edcaca;font-size:12px}.remove-selected-file:hover:not(:disabled){background:#ffe1e1}.remove-selected-file:focus-visible{outline:2px solid #174b77;outline-offset:2px}
        .history-actions-cell{width:1%;vertical-align:middle;white-space:nowrap}.history-actions{display:flex;align-items:center;gap:8px}.history-actions form{margin:0}.history-action{display:inline-flex;align-items:center;justify-content:center;gap:6px;box-sizing:border-box;min-height:40px;margin:0;padding:8px 12px;border:1px solid #c7d9ea;border-radius:7px;background:#f0f6fc;color:#174b77;font:600 13px/1.2 Arial,sans-serif;text-decoration:none;white-space:nowrap;transition:background .15s,border-color .15s}.history-action svg{width:16px;height:16px;flex-shrink:0}.history-action:hover{background:#dfedfa;border-color:#95b5d4}.history-action-danger{background:#fff5f5;border-color:#edcccc;color:#a52828}.history-action-danger:hover:not(:disabled){background:#ffe5e5;border-color:#dfa4a4}.history-action:focus-visible{outline:3px solid #90bce5;outline-offset:2px}.history-action:disabled{cursor:wait}@media(pointer:coarse){.history-action{min-height:44px}}
        .history-filters{display:flex;flex-wrap:wrap;align-items:end;gap:16px}.history-filters>div{flex:1 1 180px}.history-filters input,.history-filters select{width:100%;box-sizing:border-box;border:1px solid #c7d9ea;border-radius:6px;background:white}.history-filter-actions{display:flex;align-items:center;gap:12px}.history-filter-actions button{margin:0}.history-search{margin-bottom:20px}.history-search>div{display:flex;gap:8px;max-width:520px}.history-search input{min-width:0;flex:1;border:1px solid #c7d9ea;border-radius:6px}.history-search button{margin:0}.history-count{display:inline-block;background:#edf3fa;color:#426080;font-size:13px;font-weight:normal;padding:5px 10px;border-radius:20px}.history-pagination{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;margin-top:18px;font-size:14px}.history-pagination a{padding:8px;border:1px solid #c7d9ea;border-radius:6px;text-decoration:none;color:#174b77}
        .forms>*{min-width:0}@media(max-width:400px){.forms{grid-template-columns:minmax(0,1fr)}}
    </style>
</head>
<body><main>
    @if($admin || !$link->isDocumentOnly())
    <a href="{{ $admin ? route('assessment-links.show', $link) : route('assessment-links.public.show', $link->public_token) }}">&larr; Kembali ke asesmen</a>
    @endif
    <h1>Dokumen Klinik Rapha</h1>
    <p class="muted">Asesmen {{ $link->type_label }} · #{{ $link->id }}</p>
    @if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="notice error" role="alert"><strong>Pengiriman belum berhasil.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@if(!$errors->has('history'))<p>Pilih ulang file sebelum mengirim kembali.</p>@endif</div>@endif
    @unless($admin)
    @if($link->supportsMcuDocuments())
    <section class="panel"><h2>{{ $link->isDocumentOnly() ? 'Buat folder / batch MCU' : 'Buat folder hasil MCU detail' }}</h2>
        <form method="POST" action="{{ route('assessment-documents.folders.store', $link->public_token) }}" data-upload>
            @csrf
            <label for="folder-name">Nama folder</label>
            <input type="text" id="folder-name" name="name" maxlength="150" value="{{ old('name') }}" placeholder="Contoh: MCU September 2026" required>
            <button type="submit">Buat folder</button><p data-status role="status"></p>
        </form>
    </section>
    @endif
    @if($link->isDocumentOnly())
    <section class="panel"><h2>Pilih batch pemeriksaan</h2>
        <form method="GET" action="{{ route('assessment-documents.index', $link->public_token) }}">
            <label for="batch">Folder / batch aktif</label>
            <select id="batch" name="folder_id" required><option value="">Pilih batch</option>
                @foreach($folders as $folder)<option value="{{ $folder->id }}" @selected($selectedFolder?->id === $folder->id)>{{ $folder->name }}</option>@endforeach
            </select><button type="submit">Buka batch</button>
        </form>
        <p class="muted">Gunakan batch baru untuk periode pemeriksaan berikutnya. Urutan unggah dimulai kembali pada setiap batch.</p>
    </section>
    @endif
    <div class="forms">
        @foreach(\App\Models\AssessmentDocument::CATEGORIES as $category => $label)
        @continue(!$link->supportsMcuDocuments() && $category !== 'attendance')
        @php
            $locked = ($link->isDocumentOnly() && !$selectedFolder)
                || ($category === 'mcu_detail' && !$stages['attendance'])
                || ($category === 'mcu_recap' && (!$stages['attendance'] || !$stages['mcu_detail']));
        @endphp
        <section class="panel" data-category="{{ $category }}">
            <h2>{{ $loop->iteration }}. {{ $label }}</h2>
            <p data-stage-status role="status">{{ $stages[$category] && (!$link->isDocumentOnly() || $selectedFolder) ? 'Sudah dikirim' : ($locked ? 'Terkunci' : 'Siap diunggah') }}</p>
            @if($locked)<p class="muted" data-stage-hint>{{ $link->isDocumentOnly() && !$selectedFolder ? 'Buat atau pilih batch terlebih dahulu.' : ($category === 'mcu_detail' ? 'Aktif setelah Daftar Hadir berhasil diunggah.' : 'Aktif setelah Daftar Hadir dan minimal satu PDF MCU Detail berhasil dikirim.') }}</p>@endif
            <form method="POST" enctype="multipart/form-data" action="{{ route('assessment-documents.store', $link->public_token) }}" data-upload @if($category === 'mcu_detail') data-pdf-queue data-queue-url="{{ route('assessment-documents.queue', $link->public_token) }}" @endif>
                @csrf
                <input type="hidden" name="category" value="{{ $category }}">
                @if($link->isDocumentOnly())
                <input type="hidden" name="folder_id" value="{{ $selectedFolder?->id }}">
                @elseif($category === 'mcu_detail')
                <label for="folder_id">Folder tujuan</label>
                <select name="folder_id" id="folder_id" required @disabled($locked)>
                    <option value="">Pilih folder</option>
                    @foreach($folders as $folder)<option value="{{ $folder->id }}" @selected(old('folder_id') == $folder->id)>{{ $folder->name }}</option>@endforeach
                </select>
                @if($folders->isEmpty())<p class="muted">Buat folder terlebih dahulu melalui formulir di atas.</p>@endif
                @endif
                <label for="file-{{ $category }}" class="drop-zone {{ $locked ? 'is-disabled' : '' }}" data-drop-zone>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 16V3m-5 5 5-5 5 5M4 15v5a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-5"/></svg>
                    <strong>Tarik &amp; letakkan {{ $category === 'mcu_detail' ? 'file PDF' : 'file Excel' }} di sini</strong>
                    <span class="drop-hint">atau klik untuk memilih file</span>
                    <span class="drop-hint" id="file-hint-{{ $category }}">{{ $category === 'mcu_detail' ? 'Banyak PDF sekaligus • Dikirim bertahap otomatis' : 'Satu file .xls atau .xlsx' }} • Maks. 10 MB/file</span>
                    <input id="file-{{ $category }}" aria-describedby="file-hint-{{ $category }}" type="file" name="files[]" accept="{{ $category === 'mcu_detail' ? '.pdf' : '.xls,.xlsx' }}" @if($category === 'mcu_detail') multiple @endif required @disabled($locked)>
                </label>
                <div data-file-selection class="file-selection" role="status" aria-live="polite">Belum ada file dipilih.</div>
                <button type="submit" @disabled($locked || ($category === 'mcu_detail' && $folders->isEmpty()))>Kirim file</button>
                <p data-status role="status" aria-live="polite"></p>
                @if($category === 'mcu_detail')
                <div data-queue-panel hidden>
                    <progress data-total-progress max="1" value="0" style="width:100%" aria-label="Jumlah file tersimpan"></progress>
                    <ul data-queue-list style="padding-left:20px;max-height:320px;overflow:auto;overflow-wrap:anywhere"></ul>
                    <button type="button" data-retry hidden>Coba ulang file gagal</button>
                    <button type="button" data-new-selection hidden>Pilih kiriman baru</button>
                    <button type="button" data-refresh hidden>Perbarui riwayat</button>
                    <p class="muted">Biarkan halaman terbuka selama pengiriman. Jika gagal, coba ulang dari halaman ini; file yang sudah berhasil tidak dikirim ulang.</p>
                </div>
                @endif
            </form>
        </section>
        @endforeach
    </div>
    @endunless
    <section class="panel"><h2>Folder / batch MCU</h2>
        @forelse($folders as $folder)<p>@if($link->isDocumentOnly())<a href="{{ $admin ? route('assessment-documents.admin.index', ['assessmentLink' => $link, 'folder_id' => $folder->id]) : route('assessment-documents.index', ['token' => $link->public_token, 'folder_id' => $folder->id]) }}">{{ $folder->name }}</a>@else{{ $folder->name }}@endif</p>@empty<p class="muted">Belum ada folder.</p>@endforelse
    </section>
    @php($historyUrl = $admin ? route('assessment-documents.admin.index', $link) : route('assessment-documents.index', $link->public_token))
    @if($admin)
    <section class="panel"><h2>Filter riwayat kiriman</h2>
        <form method="GET" action="{{ $historyUrl }}" class="history-filters">
            <div><label for="history-folder">Folder / batch</label><select id="history-folder" name="folder_id"><option value="">Semua folder / batch</option>@foreach($folders as $folder)<option value="{{ $folder->id }}" @selected($selectedFolder?->id === $folder->id)>{{ $folder->name }}</option>@endforeach</select></div>
            <div><label for="history-from">Dikirim mulai (WITA)</label><input id="history-from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div><label for="history-to">Dikirim sampai (WITA)</label><input id="history-to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}"></div>
            @foreach(\App\Models\AssessmentDocument::CATEGORIES as $key => $label)
                @if(!empty($filters[$key.'_search']))<input type="hidden" name="{{ $key }}_search" value="{{ $filters[$key.'_search'] }}">@endif
            @endforeach
            <div class="history-filter-actions"><button type="submit">Terapkan filter</button><a href="{{ $historyUrl }}">Reset semua</a></div>
        </form>
    </section>
    @endif
    @foreach(\App\Models\AssessmentDocument::CATEGORIES as $category => $label)
    @php($documents = $histories[$category])
    <section class="panel" id="history-{{ $category }}"><h2>Riwayat {{ $label }} <span class="history-count">{{ $documents->total() }} file</span></h2>
        <form method="GET" action="{{ $historyUrl }}#history-{{ $category }}" class="history-search">
            @foreach($filters as $key => $value)
                @if($key !== $category.'_search' && $value !== null && $value !== '')<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
            @endforeach
            <label for="search-{{ $category }}">Cari nama file</label>
            <div><input id="search-{{ $category }}" type="search" name="{{ $category }}_search" maxlength="150" value="{{ $filters[$category.'_search'] ?? '' }}" placeholder="Cari file {{ $label }}"><button type="submit">Cari</button></div>
        </form>
        <div class="scroll"><table><thead><tr><th>Dokumen</th><th>Folder / batch</th><th>Ukuran</th><th>Dikirim</th><th class="history-actions-cell" scope="col">Aksi</th></tr></thead><tbody>
            @forelse($documents as $document)
            <tr><td>{{ $document->original_name }}</td><td>{{ $document->folder?->name ?: 'Tanpa folder' }}</td><td>{{ number_format($document->size / 1024, 1) }} KB</td><td>{{ $document->created_at->timezone('Asia/Makassar')->format('d-m-Y H:i') }}</td><td class="history-actions-cell"><div class="history-actions"><a class="history-action" aria-label="Unduh {{ $document->original_name }}" href="{{ $admin ? route('assessment-documents.admin.download', [$link, $document->id]) : route('assessment-documents.download', [$link->public_token, $document->id]) }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3v12m-5-5 5 5 5-5M4 16v4h16v-4"/></svg><span>Unduh</span></a>
                <form method="POST" action="{{ $admin ? route('assessment-documents.admin.destroy', [$link, $document->id]) : route('assessment-documents.destroy', [$link->public_token, $document->id]) }}" data-delete-history data-file-name="{{ $document->original_name }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="history-action history-action-danger" aria-label="Hapus riwayat {{ $document->original_name }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/></svg><span>Hapus</span></button>
                </form>
            </div></td></tr>
            @empty<tr><td colspan="5">Tidak ada file yang sesuai dengan pencarian atau filter.</td></tr>@endforelse
        </tbody></table></div>
        <nav class="history-pagination" aria-label="Halaman riwayat {{ $label }}">
            @if($documents->previousPageUrl())<a href="{{ $documents->previousPageUrl() }}">&larr; Sebelumnya</a>@endif
            <span>Halaman {{ $documents->currentPage() }} dari {{ $documents->lastPage() }}</span>
            @if($documents->nextPageUrl())<a href="{{ $documents->nextPageUrl() }}">Berikutnya &rarr;</a>@endif
        </nav>
    </section>
    @endforeach
</main>
<script>
document.querySelectorAll('[data-upload]:not([data-pdf-queue])').forEach(form => {
    form.addEventListener('submit', event => {
        const input = form.querySelector('input[type=file]');
        const status = form.querySelector('[data-status]');
        if (input && (input.files.length > (input.multiple ? 10 : 1) || Array.from(input.files).some(file => file.size > 10 * 1024 * 1024))) {
            event.preventDefault();
            status.textContent = 'Periksa jumlah file dan batas ukuran 10 MB per file.';
            return;
        }
        form.dataset.submitting = 'true';
        form.querySelector('button[type=submit]').disabled = true;
        form.querySelectorAll('.remove-selected-file').forEach(button => { button.disabled = true; });
        status.textContent = input ? 'Sedang mengirim file. Mohon tunggu sampai selesai…' : 'Sedang membuat folder…';
    });
});
</script>
<script src="{{ versioned_asset('admin/js/assessment-document-upload.js') }}"></script>
<script src="{{ asset('vendor/sweetalert/sweetalert.all.js') }}"></script>
<script>
document.querySelectorAll('[data-delete-history]').forEach(form => {
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const button = form.querySelector('button');
        if (button.disabled) return;
        button.disabled = true;
        const result = await Swal.fire({
            title: 'Hapus riwayat kiriman?',
            text: form.dataset.fileName + ' akan dihapus dari riwayat dan tidak bisa diunduh lagi. File tetap tersimpan privat sebagai arsip.',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Ya, hapus riwayat', cancelButtonText: 'Batal', focusCancel: true
        });
        if (result.isConfirmed) {
            button.textContent = 'Menghapus�';
            form.submit();
        } else {
            button.disabled = false;
        }
    });
});
</script>
</body></html>
