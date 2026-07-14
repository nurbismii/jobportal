@extends('layouts.app-pic')

@section('content-admin')
<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <div>
        <h2 class="m-0 font-weight-bold text-primary">Link Asesmen</h2>
        <div class="small text-muted">Kelola tautan pengisian hasil tes untuk petugas.</div>
    </div>
    <a href="{{ route('assessment-links.create') }}" class="btn btn-primary btn-sm mt-2 mt-md-0"><i class="fas fa-plus"></i> Buat Link</a>
</div>

@if(session('assessment_link_url'))
<div class="alert alert-success d-flex flex-wrap align-items-center justify-content-between" role="alert">
    <div class="mr-2"><strong>URL publik baru:</strong> <span id="newAssessmentLinkUrl">{{ session('assessment_link_url') }}</span></div>
    <button type="button" class="btn btn-sm btn-outline-success mt-2 mt-md-0" data-copy-target="newAssessmentLinkUrl">Salin URL</button>
</div>
@endif

<div class="card shadow">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead><tr><th>Tipe</th><th>Pembuat</th><th>Lowongan / Posisi</th><th>Kedaluwarsa</th><th>Status</th><th>Kandidat</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse($links as $link)
                    @php($expired = $link->expires_at && $link->expires_at->lte(now('Asia/Makassar')))
                    @php($positions = $link->candidates->map(fn ($candidate) => optional(optional($candidate->lamaran)->lowongan)->nama_lowongan)->filter()->countBy())
                    <tr>
                        <td>{{ ucfirst($link->assessment_type) }}</td>
                        <td>{{ optional($link->creator)->name ?: '-' }}</td>
                        <td>
                            @forelse($positions as $position => $count)
                                <div>{{ $position }} ({{ $count }})</div>
                            @empty
                                <span class="text-muted">-</span>
                            @endforelse
                        </td>
                        <td>{{ optional($link->expires_at)->format('d-m-Y H:i') ?: '-' }}</td>
                        <td>
                            @if(!$link->is_active)<span class="badge badge-secondary">Nonaktif</span>
                            @elseif($expired)<span class="badge badge-warning">Kedaluwarsa</span>
                            @else<span class="badge badge-success">Aktif</span>
                            @endif
                        </td>
                        <td>{{ $link->candidates_count }}</td>
                        <td><a href="{{ route('assessment-links.show', $link) }}" class="btn btn-info btn-sm">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada link asesmen. Buat link baru untuk memilih kandidat.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $links->links() }}</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-copy-target]').forEach(function (button) {
    button.addEventListener('click', function () {
        var value = document.getElementById(button.dataset.copyTarget).textContent.trim();
        if (navigator.clipboard) navigator.clipboard.writeText(value);
        button.textContent = 'Tersalin';
    });
});
</script>
@endpush
