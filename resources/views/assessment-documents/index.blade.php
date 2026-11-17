<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Dokumen Klinik Rapha</title>
    <link rel="stylesheet" href="{{ versioned_asset('admin/css/assessment-documents.css') }}">
</head>
<body>
<a class="skip-link button" href="#main-content">Lewati ke konten utama</a>
<main id="main-content">
    @php
        $historyUrl = $admin ? route('assessment-documents.admin.index', $link) : route('assessment-documents.index', $link->public_token);
        $canOpenFolder = $admin || $link->isDocumentOnly();
    @endphp

    @if($admin || !$link->isDocumentOnly())
        <a class="back-link" href="{{ $admin ? route('assessment-links.show', $link) : route('assessment-links.public.show', $link->public_token) }}">&larr; Kembali ke asesmen</a>
    @endif

    <header class="page-header">
        <div class="heading-group">
            <div class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6z"/></svg>
            </div>
            <div>
                <p class="eyebrow">Portal dokumen klinik</p>
                <h1>Dokumen Klinik Rapha</h1>
                <p class="page-description">{{ $admin ? 'Tinjau dokumen pemeriksaan, pilih batch, dan temukan riwayat kiriman klinik.' : 'Kelola batch pemeriksaan dan kirim dokumen secara berurutan dalam satu tempat.' }}</p>
                <div class="page-meta"><span class="access-badge">{{ $admin ? 'Akses admin' : 'Akses klinik' }}</span><span>Asesmen {{ $link->type_label }} &middot; #{{ $link->id }}</span></div>
            </div>
        </div>
        <nav class="page-actions" aria-label="Navigasi dokumen">
            @unless($admin)<a class="button" href="#upload-documents">Unggah dokumen</a>@endunless
            <a class="button button-secondary" href="#document-history">Lihat riwayat</a>
        </nav>
    </header>

    @if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="notice error" role="alert">
            <strong>Pengiriman belum berhasil.</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            @if(!$errors->has('history'))<p>Pilih ulang file sebelum mengirim kembali.</p>@endif
        </div>
    @endif

    @unless($admin)
        @if($link->supportsMcuDocuments())
            <div class="batch-setup">
                <section class="panel" aria-labelledby="create-folder-heading">
                    <div class="panel-heading"><div>
                        <h2 id="create-folder-heading">{{ $link->isDocumentOnly() ? 'Buat folder / batch MCU' : 'Buat folder hasil MCU detail' }}</h2>
                        <p>Siapkan folder untuk periode pemeriksaan baru.</p>
                    </div></div>
                    <form method="POST" action="{{ route('assessment-documents.folders.store', $link->public_token) }}" data-upload>
                        @csrf
                        <label for="folder-name">Nama folder</label>
                        <div class="inline-form">
                            <input type="text" id="folder-name" name="name" maxlength="150" value="{{ old('name') }}" placeholder="Contoh: MCU November 2026" required>
                            <button type="submit">Buat folder</button>
                        </div>
                        <p data-status role="status"></p>
                    </form>
                </section>

                @if($link->isDocumentOnly())
                    <section class="panel" aria-labelledby="select-batch-heading">
                        <div class="panel-heading"><div>
                            <h2 id="select-batch-heading">Pilih batch pemeriksaan</h2>
                            <p>Buka batch untuk melanjutkan unggahan dan melihat kirimannya.</p>
                        </div></div>
                        <form method="GET" action="{{ $historyUrl }}">
                            <label for="batch">Folder / batch aktif</label>
                            <div class="inline-form">
                                <select id="batch" name="folder_id" required>
                                    <option value="">Pilih batch</option>
                                    @foreach($folders as $folder)<option value="{{ $folder->id }}" @selected($selectedFolder?->id === $folder->id)>{{ $folder->name }}</option>@endforeach
                                </select>
                                <button type="submit">Buka batch</button>
                            </div>
                        </form>
                        <p class="help">Urutan unggah dimulai kembali pada setiap batch baru.</p>
                    </section>
                @endif
            </div>
        @endif
    @endunless

    <section class="panel" id="document-folders" aria-labelledby="folders-heading">
        <div class="panel-heading">
            <div><h2 id="folders-heading">Folder / batch MCU</h2><p>{{ $folders->count() }} folder &middot; Tanggal pembuatan dalam WITA</p></div>
            <span class="sort-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 4v16m-4-4 4 4 4-4M13 5h8m-8 5h6m-6 5h4"/></svg>Terbaru ke terlama</span>
        </div>
        @if($folders->isNotEmpty())
            <ul class="folder-list">
                @foreach($folders as $folder)
                    <li>
                        @if($canOpenFolder)
                            <a class="folder-item {{ $selectedFolder?->id === $folder->id ? 'is-active' : '' }}" href="{{ $historyUrl.'?'.http_build_query(array_merge($filters, ['folder_id' => $folder->id])) }}" @if($selectedFolder?->id === $folder->id) aria-current="true" @endif>
                        @else
                            <div class="folder-item">
                        @endif
                            <svg class="folder-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 7V5a1 1 0 0 1 1-1h6l2 3h8a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7Z"/><path d="M3 8h18"/></svg>
                            <span class="folder-content">
                                <span class="folder-name">{{ $folder->name }}</span>
                                <span class="folder-date">Dibuat {{ $folder->created_at?->timezone('Asia/Makassar')->locale('id')->translatedFormat('d M Y, H:i') ?? '—' }} WITA</span>
                            </span>
                            @if($selectedFolder?->id === $folder->id)<span class="folder-state">Aktif</span>@elseif($canOpenFolder)<span class="folder-state" aria-hidden="true">&rarr;</span>@endif
                        @if($canOpenFolder)</a>@else</div>@endif
                    </li>
                @endforeach
            </ul>
        @else
            <p class="empty-state"><strong>Belum ada folder.</strong>{{ $admin ? 'Folder pemeriksaan yang dibuat klinik akan tampil di sini.' : 'Buat folder pertama untuk mengelompokkan dokumen pemeriksaan.' }}</p>
        @endif
    </section>

    @unless($admin)
        <div class="section-heading" id="upload-documents">
            <div><h2>Unggah dokumen</h2><p>{{ $link->supportsMcuDocuments() ? 'Ikuti urutan 1–3. Tahap berikutnya terbuka setelah dokumen sebelumnya tersimpan.' : 'Kirim daftar hadir pemeriksaan dalam format Excel.' }}</p></div>
        </div>
        @if($link->isDocumentOnly() && $selectedFolder)
            <div class="batch-context"><span>Batch tujuan unggahan</span><strong>{{ $selectedFolder->name }}</strong></div>
        @endif
        <div class="forms">
            @foreach(\App\Models\AssessmentDocument::CATEGORIES as $category => $label)
                @continue(!$link->supportsMcuDocuments() && $category !== 'attendance')
                @php
                    $locked = ($link->isDocumentOnly() && !$selectedFolder)
                        || ($category === 'mcu_detail' && !$stages['attendance'])
                        || ($category === 'mcu_recap' && (!$stages['attendance'] || !$stages['mcu_detail']));
                @endphp
                <section class="panel {{ $locked ? 'is-locked' : '' }}" data-category="{{ $category }}" aria-labelledby="stage-{{ $category }}">
                    <div class="stage-heading"><span class="step-number" aria-hidden="true">{{ $loop->iteration }}</span><h3 id="stage-{{ $category }}">{{ $label }}</h3></div>
                    <p data-stage-status role="status">{{ $stages[$category] && (!$link->isDocumentOnly() || $selectedFolder) ? 'Sudah dikirim' : ($locked ? 'Terkunci' : 'Siap diunggah') }}</p>
                    @if($locked)<p class="muted" data-stage-hint>{{ $link->isDocumentOnly() && !$selectedFolder ? 'Buat atau pilih batch terlebih dahulu.' : ($category === 'mcu_detail' ? 'Aktif setelah Daftar Hadir berhasil diunggah.' : 'Aktif setelah Daftar Hadir dan minimal satu PDF MCU Detail berhasil dikirim.') }}</p>@endif

                    <form class="upload-form" method="POST" enctype="multipart/form-data" action="{{ route('assessment-documents.store', $link->public_token) }}" data-upload @if($category === 'mcu_detail') data-pdf-queue data-queue-url="{{ route('assessment-documents.queue', $link->public_token) }}" @endif>
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
                                <progress data-total-progress max="1" value="0" aria-label="Jumlah file tersimpan"></progress>
                                <ul data-queue-list></ul>
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

    <div class="section-heading" id="document-history"><div><h2>Riwayat kiriman</h2><p>Temukan, unduh, dan kelola dokumen yang sudah dikirim.</p></div></div>

    @if($admin)
        <section class="panel" aria-labelledby="filters-heading">
            <div class="panel-heading"><div><h3 id="filters-heading">Filter riwayat kiriman</h3><p>Batasi hasil berdasarkan batch dan tanggal pengiriman.</p></div></div>
            <form method="GET" action="{{ $historyUrl }}#document-history" class="history-filters">
                <div><label for="history-folder">Folder / batch</label><select id="history-folder" name="folder_id"><option value="">Semua folder / batch</option>@foreach($folders as $folder)<option value="{{ $folder->id }}" @selected($selectedFolder?->id === $folder->id)>{{ $folder->name }}</option>@endforeach</select></div>
                <div><label for="history-from">Dikirim mulai (WITA)</label><input id="history-from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}"></div>
                <div><label for="history-to">Dikirim sampai (WITA)</label><input id="history-to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}"></div>
                @foreach(\App\Models\AssessmentDocument::CATEGORIES as $key => $label)
                    @if(!empty($filters[$key.'_search']))<input type="hidden" name="{{ $key }}_search" value="{{ $filters[$key.'_search'] }}">@endif
                @endforeach
                <div class="history-filter-actions"><button type="submit">Terapkan filter</button><a href="{{ $historyUrl }}#document-history">Reset semua</a></div>
            </form>
        </section>
    @endif

    <nav class="history-tabs" aria-label="Kategori riwayat kiriman">
        @foreach(\App\Models\AssessmentDocument::CATEGORIES as $category => $label)<a href="#history-{{ $category }}">{{ $label }} ({{ $histories[$category]->total() }})</a>@endforeach
    </nav>

    @foreach(\App\Models\AssessmentDocument::CATEGORIES as $category => $label)
        @php($documents = $histories[$category])
        <section class="panel history-panel" id="history-{{ $category }}" aria-labelledby="history-heading-{{ $category }}">
            <div class="panel-heading"><h3 id="history-heading-{{ $category }}">Riwayat {{ $label }}</h3><span class="history-count">{{ $documents->total() }} file</span></div>
            <form method="GET" action="{{ $historyUrl }}#history-{{ $category }}" class="history-search">
                @foreach($filters as $key => $value)
                    @if($key !== $category.'_search' && $value !== null && $value !== '')<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                @endforeach
                <label for="search-{{ $category }}">Cari nama file</label>
                <div><input id="search-{{ $category }}" type="search" name="{{ $category }}_search" maxlength="150" value="{{ $filters[$category.'_search'] ?? '' }}" placeholder="Cari file {{ $label }}"><button type="submit">Cari</button></div>
            </form>
            <div class="scroll" role="region" aria-label="Daftar {{ $label }}" tabindex="0">
                <table role="table"><thead><tr><th scope="col">Dokumen</th><th scope="col">Folder / batch</th><th scope="col">Ukuran</th><th scope="col">Dikirim (WITA)</th><th class="history-actions-cell" scope="col">Aksi</th></tr></thead><tbody>
                    @forelse($documents as $document)
                        <tr>
                            <td class="document-name" data-label="Dokumen">{{ $document->original_name }}</td>
                            <td class="document-folder" data-label="Folder / batch">{{ $document->folder?->name ?: 'Tanpa folder' }}</td>
                            <td data-label="Ukuran">{{ number_format($document->size / 1024, 1) }} KB</td>
                            <td class="document-date" data-label="Dikirim (WITA)">{{ $document->created_at->timezone('Asia/Makassar')->format('d-m-Y') }}<small>{{ $document->created_at->timezone('Asia/Makassar')->format('H:i') }} WITA</small></td>
                            <td class="history-actions-cell"><div class="history-actions">
                                <a class="history-action" aria-label="Unduh {{ $document->original_name }}" href="{{ $admin ? route('assessment-documents.admin.download', [$link, $document->id]) : route('assessment-documents.download', [$link->public_token, $document->id]) }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3v12m-5-5 5 5 5-5M4 16v4h16v-4"/></svg><span>Unduh</span></a>
                                @if(!$admin || auth()->user()->hasModulePermission('assessment', 'delete'))
                                    <form method="POST" action="{{ $admin ? route('assessment-documents.admin.destroy', [$link, $document->id]) : route('assessment-documents.destroy', [$link->public_token, $document->id]) }}" data-delete-history data-file-name="{{ $document->original_name }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="history-action history-action-danger" aria-label="Hapus riwayat {{ $document->original_name }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/></svg><span>Hapus</span></button>
                                    </form>
                                @endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td class="history-empty" colspan="5"><div class="empty-state"><strong>Belum ada dokumen yang ditampilkan.</strong>Tidak ada file yang sesuai dengan pencarian atau filter.</div></td></tr>
                    @endforelse
                </tbody></table>
            </div>
            <nav class="history-pagination" aria-label="Halaman riwayat {{ $label }}">
                <span>{{ $documents->total() }} file &middot; Halaman {{ $documents->currentPage() }} dari {{ $documents->lastPage() }}</span>
                <div>
                    @if($documents->previousPageUrl())<a href="{{ $documents->previousPageUrl() }}">&larr; Sebelumnya</a>@endif
                    @if($documents->nextPageUrl())<a href="{{ $documents->nextPageUrl() }}">Berikutnya &rarr;</a>@endif
                </div>
            </nav>
        </section>
    @endforeach
    <footer class="page-footer">Dokumen Klinik Rapha &middot; Seluruh waktu ditampilkan dalam WITA (UTC+8)</footer>
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
            button.textContent = 'Menghapus…';
            form.submit();
        } else {
            button.disabled = false;
        }
    });
});
</script>
</body>
</html>
