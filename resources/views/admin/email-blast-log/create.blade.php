@extends('layouts.app-pic')

@section('content-admin')
<h1 class="h3 mb-2 text-gray-800">Blast Email HR</h1>
<p class="mb-4">Kirim pesan kepada pengguna aktif yang emailnya sudah terverifikasi.</p>
@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Permintaan belum diproses.</strong>
        <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="POST" action="{{ route('email-blast-log.store') }}" id="blast-form" enctype="multipart/form-data" data-permission-allowed="{{ auth()->user()->canAccessAdminRoute('email-blast-log.store') ? '1' : '0' }}">
            @csrf
            <div class="form-group">
                <label for="recipients">Penerima email</label>
                <textarea class="form-control" id="recipients" name="recipients" rows="5" maxlength="20000" required aria-describedby="recipient-help" placeholder="pelamar1@example.com&#10;pelamar2@example.com">{{ old('recipients') }}</textarea>
                <small id="recipient-help" class="form-text text-muted">Tempel maksimal 200 alamat. Pisahkan dengan baris baru, koma, titik koma, atau spasi. Duplikat hanya dikirim sekali. Setiap penerima menerima email terpisah.</small>
                <small id="recipient-count" class="form-text text-muted" aria-live="polite"></small>
            </div>
            <div class="form-group">
                <label for="subject">Subjek email</label>
                <input class="form-control" id="subject" name="subject" maxlength="200" value="{{ old('subject') }}" required>
            </div>
            <div class="form-group">
                <label for="message">Pesan</label>
                <textarea class="form-control" id="message" name="message" rows="8" maxlength="10000">{{ old('message') }}</textarea>
                <small class="form-text text-muted">Gunakan huruf tebal, miring, judul, dan daftar. Maksimal 10.000 karakter termasuk format HTML.</small>
                <small id="message-error" class="text-danger" role="alert" hidden></small>
            </div>
            <div class="form-group">
                <label for="attachments">Lampiran (opsional)</label>
                <input type="file" class="form-control-file" id="attachments" name="attachments[]" multiple accept=".pdf,.xls,.xlsx,.csv,.doc,.docx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif,.webp" aria-describedby="attachment-help">
                <small id="attachment-help" class="form-text text-muted">PDF, Excel, Word, PowerPoint, teks, CSV, dan foto. Maksimal 5 file, 5 MB per file, total 10 MB. Lampiran yang sama dikirim ke seluruh penerima. Setelah validasi gagal, pilih ulang file.</small>
                <ul id="attachment-list" class="small mt-2 mb-0" aria-live="polite"></ul>
                <small id="attachment-error" class="text-danger" role="alert" hidden></small>
            </div>
            <button class="btn btn-primary" type="submit" id="send-button"><i class="fas fa-paper-plane mr-1" aria-hidden="true"></i> Kirim ke antrean</button>
        </form>
    </div>
</div>
<div class="card shadow mb-4" id="delivery-history">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <h2 class="h6 m-0 font-weight-bold text-primary">Riwayat pengiriman HR</h2>
        @if(auth()->user()->canAccessAdminRoute('email-blast-log.create'))
        <a class="btn btn-sm btn-outline-primary" href="{{ request()->fullUrl() }}#delivery-history"><i class="fas fa-sync-alt mr-1" aria-hidden="true"></i> Perbarui status</a>
        @endif
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('email-blast-log.create') }}#delivery-history" class="mb-3">
            <div class="form-row align-items-end">
                <div class="col-md-4 col-lg-3 form-group mb-md-0">
                    <label for="email-status">Status email</label>
                    <select name="status" id="email-status" class="form-control">
                        <option value="">Semua status</option>
                        @foreach(\App\Models\HrEmailDelivery::STATUS_LABELS as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary">Terapkan filter</button>
                    @if($status)
                        <a href="{{ route('email-blast-log.create') }}#delivery-history" class="btn btn-outline-secondary ml-1">Reset</a>
                    @endif
                </div>
            </div>
        </form>
        <p class="small text-muted mb-2">Antrean diproses bertahap. Terkirim berarti server email menerima pesan; bukan jaminan pesan masuk inbox. Dilewati berarti akun atau alamat berubah sebelum pengiriman.</p>
        <p class="small text-muted">Retry menggunakan pesan dan lampiran sebelumnya. Jika kegagalan terjadi karena timeout, periksa pengiriman terlebih dahulu karena server mungkin sudah menerima email.</p>
        @php($canRetry = auth()->user()->canAccessAdminRoute('email-blast-log.retry'))
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <span class="small text-muted mb-2 mb-sm-0">{{ number_format($deliveries->total(), 0, ',', '.') }} pengiriman{{ $status ? ' · '.\App\Models\HrEmailDelivery::STATUS_LABELS[$status] : '' }}</span>
            @if($canRetry)
                <form method="POST" action="{{ route('email-blast-log.retry') }}" id="retry-selected-form" class="retry-form" data-permission-allowed="1">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary" id="retry-selected-button" disabled>
                        <i class="fas fa-redo mr-1" aria-hidden="true"></i> Retry terpilih (<span id="retry-selected-count">0</span>)
                    </button>
                </form>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr>
                    @if($canRetry)
                        <th scope="col"><input type="checkbox" id="select-all-failed" aria-label="Pilih semua email gagal di halaman ini" @disabled(! $deliveries->contains('status', 'failed'))></th>
                    @endif
                    <th scope="col">Penerima</th><th scope="col">Subjek</th><th scope="col">Status</th><th scope="col">Dibuat</th><th scope="col">Terkirim</th>
                    @if($canRetry)<th scope="col">Tindakan</th>@endif
                </tr></thead>
                <tbody>
                    @forelse($deliveries as $delivery)
                        <tr>
                            @if($canRetry)
                                <td>
                                    @if($delivery->status === 'failed')
                                        <input type="checkbox" class="retry-checkbox" name="delivery_ids[]" value="{{ $delivery->id }}" form="retry-selected-form" aria-label="Pilih email gagal ke {{ $delivery->recipient }}">
                                    @endif
                                </td>
                            @endif
                            <td>{{ $delivery->recipient }}</td><td>{{ $delivery->subject }}</td>
                            <td>
                                @switch($delivery->status)
                                    @case('sent') <span class="badge badge-success">Terkirim</span> @break
                                    @case('failed') <span class="badge badge-danger">Gagal</span> @break
                                    @case('skipped') <span class="badge badge-secondary">Dilewati</span> @break
                                    @case('retrying') <span class="badge badge-info">Menunggu retry</span> @break
                                    @case('processing') <span class="badge badge-primary">Sedang dikirim</span> @break
                                    @default <span class="badge badge-warning">Menunggu antrean</span>
                                @endswitch
                            </td>
                            <td>{{ $delivery->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $delivery->sent_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            @if($canRetry)
                                <td>
                                    @if($delivery->status === 'failed')
                                        <form method="POST" action="{{ route('email-blast-log.retry') }}" class="retry-form" data-permission-allowed="1">
                                            @csrf
                                            <input type="hidden" name="delivery_ids[]" value="{{ $delivery->id }}">
                                            <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap" aria-label="Retry email ke {{ $delivery->recipient }}"><i class="fas fa-redo mr-1" aria-hidden="true"></i> Retry</button>
                                        </form>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canRetry ? 7 : 5 }}" class="text-center text-muted py-4">{{ $status ? 'Tidak ada pengiriman dengan status ini.' : 'Belum ada pengiriman email HR.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $deliveries->links() }}
    </div>
</div>
@push('scripts')
<script src="{{ versioned_asset('vendor/tinymce/tinymce.min.js') }}"></script>
<script>
    const retryCheckboxes = Array.from(document.querySelectorAll('.retry-checkbox'));
    const selectAllFailed = document.getElementById('select-all-failed');
    const updateRetrySelection = () => {
        const count = retryCheckboxes.filter(checkbox => checkbox.checked).length;
        document.getElementById('retry-selected-count').textContent = count;
        document.getElementById('retry-selected-button').disabled = count === 0;
        selectAllFailed.checked = count > 0 && count === retryCheckboxes.length;
        selectAllFailed.indeterminate = count > 0 && count < retryCheckboxes.length;
    };
    if (selectAllFailed) {
        selectAllFailed.addEventListener('change', () => {
            retryCheckboxes.forEach(checkbox => { checkbox.checked = selectAllFailed.checked; });
            updateRetrySelection();
        });
        retryCheckboxes.forEach(checkbox => checkbox.addEventListener('change', updateRetrySelection));
        updateRetrySelection();
    }
    document.querySelectorAll('.retry-form').forEach(form => {
        form.addEventListener('submit', event => {
            const count = form.id === 'retry-selected-form' ? retryCheckboxes.filter(checkbox => checkbox.checked).length : 1;
            if (!count || !window.confirm(`Masukkan ${count} email gagal kembali ke antrean?`)) {
                event.preventDefault();
                return;
            }
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = 'Memasukkan ke antrean…';
        });
    });
    tinymce.init({
        selector: '#message',
        height: 350,
        menubar: false,
        plugins: 'lists',
        toolbar: 'undo redo | blocks | bold italic underline strikethrough | bullist numlist | removeformat',
        block_formats: 'Paragraf=p; Judul=h2; Subjudul=h3; Kutipan=blockquote',
        branding: false,
        promotion: false
    });
    const recipients = document.getElementById('recipients');
    const updateCount = () => {
        const count = new Set(recipients.value.toLowerCase().split(/[\s,;]+/).filter(Boolean)).size;
        document.getElementById('recipient-count').textContent = `${count} alamat unik / maksimal 200`;
    };
    recipients.addEventListener('input', updateCount);
    updateCount();
    const attachmentInput = document.getElementById('attachments');
    const validateAttachments = () => {
        const files = Array.from(attachmentInput.files);
        const error = document.getElementById('attachment-error');
        const list = document.getElementById('attachment-list');
        list.replaceChildren();
        files.forEach(file => {
            const item = document.createElement('li');
            item.textContent = `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
            list.appendChild(item);
        });
        const invalid = files.length > 5 || files.some(file => file.size > 5 * 1024 * 1024) || files.reduce((total, file) => total + file.size, 0) > 10 * 1024 * 1024;
        error.hidden = !invalid;
        error.textContent = 'Maksimal 5 file, 5 MB per file, dan total 10 MB.';
        return !invalid;
    };
    attachmentInput.addEventListener('change', validateAttachments);
    document.getElementById('blast-form').addEventListener('submit', (event) => {
        if (!validateAttachments()) {
            event.preventDefault();
            return;
        }
        tinymce.triggerSave();
        const editor = tinymce.get('message');
        const content = document.getElementById('message').value;
        const text = editor ? editor.getContent({ format: 'text' }) : content;
        if (!text.replace(/[\s\u00a0\u200b]/g, '') || content.length > 10000) {
            event.preventDefault();
            const error = document.getElementById('message-error');
            error.textContent = content.length > 10000 ? 'Pesan melebihi 10.000 karakter termasuk format HTML.' : 'Pesan tidak boleh kosong.';
            error.hidden = false;
            if (editor) editor.focus();
            return;
        }
        const button = document.getElementById('send-button');
        button.disabled = true;
        button.textContent = 'Memasukkan ke antrean…';
    });
</script>
@endpush
@endsection
