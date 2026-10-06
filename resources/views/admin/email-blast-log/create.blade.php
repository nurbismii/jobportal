@extends('layouts.app-pic')

@section('content-admin')
<h1 class="h3 mb-2 text-gray-800">Blast Email HR</h1>
<p class="mb-4">Kirim pesan kepada pengguna aktif yang emailnya sudah terverifikasi.</p>
@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Pengiriman belum diproses.</strong>
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
<div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="h6 m-0 font-weight-bold text-primary">Riwayat pengiriman HR</h2>
        @if(auth()->user()->canAccessAdminRoute('email-blast-log.create'))
        <a class="btn btn-sm btn-outline-primary" href="{{ route('email-blast-log.create') }}">Perbarui status</a>
        @endif
    </div>
    <div class="card-body">
        <p class="small text-muted">Antrean diproses bertahap. Terkirim berarti server email menerima pesan; bukan jaminan pesan masuk inbox. Dilewati berarti akun atau alamat berubah sebelum pengiriman.</p>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Penerima</th><th>Subjek</th><th>Status</th><th>Dibuat</th><th>Terkirim</th></tr></thead>
                <tbody>
                    @forelse($deliveries as $delivery)
                        <tr>
                            <td>{{ $delivery->recipient }}</td><td>{{ $delivery->subject }}</td>
                            <td>
                                @switch($delivery->status)
                                    @case('sent') <span class="badge badge-success">Terkirim</span> @break
                                    @case('failed') <span class="badge badge-danger">Gagal</span> @break
                                    @case('skipped') <span class="badge badge-secondary">Dilewati</span> @break
                                    @default <span class="badge badge-warning">Menunggu</span>
                                @endswitch
                            </td>
                            <td>{{ $delivery->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $delivery->sent_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Belum ada pengiriman email HR.</td></tr>
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
