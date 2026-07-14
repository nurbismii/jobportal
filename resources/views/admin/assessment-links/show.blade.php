@extends('layouts.app-pic')

@section('content-admin')
@php($expired = $link->expires_at && $link->expires_at->lte(now('Asia/Makassar')))
<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <div>
        <h2 class="m-0 font-weight-bold text-primary">Detail Link Asesmen</h2>
        <div class="small text-muted">{{ ucfirst($link->assessment_type) }} · dibuat oleh {{ optional($link->creator)->name ?: '-' }}</div>
    </div>
    <a href="{{ route('assessment-links.index') }}" class="btn btn-secondary btn-sm mt-2 mt-md-0">Kembali</a>
</div>

<div class="card shadow mb-3"><div class="card-body"><div class="row">
    <div class="col-md-8 mb-2">
        <label class="small font-weight-bold">URL publik</label>
        <div class="input-group"><input id="assessmentPublicUrl" class="form-control" readonly value="{{ route('assessment-links.public.show', $link->public_token) }}"><div class="input-group-append"><button type="button" class="btn btn-outline-primary" data-copy-target="assessmentPublicUrl">Salin</button></div></div>
        <div class="small text-muted mt-1">PIN tidak ditampilkan kembali demi keamanan.</div>
    </div>
    <div class="col-md-4 mb-2">
        <div>Kedaluwarsa: {{ optional($link->expires_at)->format('d-m-Y H:i') ?: '-' }}</div>
        <div>Status: @if(!$link->is_active) Nonaktif @elseif($expired) Kadaluwarsa @else Aktif @endif</div>
        @if($link->is_active)
        <form id="deactivateAssessmentLinkForm" method="POST" action="{{ route('assessment-links.deactivate', $link) }}" class="mt-2">@csrf<button type="button" class="btn btn-danger btn-sm" data-deactivate-link>Nonaktifkan link</button></form>
        @endif
    </div>
</div></div></div>

<div class="card shadow mb-3">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center"><strong>Tambah kandidat</strong><span class="small text-muted">Hanya status {{ $link->assessment_type === 'kesehatan' ? 'Tes Kesehatan' : 'Tes Lapangan' }}</span></div>
    <div class="card-body">
        <form method="GET" action="{{ route('assessment-links.show', $link) }}" class="mb-3">
            <div class="input-group">
                <input type="search" name="candidate_search" class="form-control" value="{{ $candidateSearch }}" placeholder="Cari nama atau nomor KTP" autocomplete="off">
                <div class="input-group-append"><button class="btn btn-outline-primary" type="submit">Cari</button></div>
            </div>
        </form>
        @if($errors->has('lamaran_ids'))<div class="alert alert-danger">{{ $errors->first('lamaran_ids') }}</div>@endif
        <form method="POST" action="{{ route('assessment-links.candidates.store', $link) }}">
            @csrf
            <div class="table-responsive"><table class="table table-sm table-bordered mb-2"><thead><tr><th class="text-center" style="width:45px">Pilih</th><th>Nama</th><th>No. KTP</th><th>Posisi dilamar</th><th>Status proses</th></tr></thead><tbody>
            @forelse($eligibleLamarans as $lamaran)
            <tr>
                <td class="text-center"><input type="checkbox" name="lamaran_ids[]" value="{{ $lamaran->id }}" {{ in_array($lamaran->id, old('lamaran_ids', [])) ? 'checked' : '' }}></td>
                <td>{{ optional(optional($lamaran->biodata)->user)->name ?: '-' }}</td>
                <td>{{ optional($lamaran->biodata)->no_ktp ?: '-' }}</td>
                <td>@if($lamaran->lowongan){{ $lamaran->lowongan->nama_lowongan }} - {{ $lamaran->lowongan->created_at ? tanggalIndo($lamaran->lowongan->created_at->toDateString()) : '-' }}@else-@endif</td>
                <td>{{ $lamaran->status_proses }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada kandidat yang dapat ditambahkan.</td></tr>
            @endforelse
            </tbody></table></div>
            @if($eligibleLamarans->count())<button class="btn btn-primary btn-sm" type="submit">Tambah kandidat terpilih</button>@endif
        </form>
        <div class="mt-3">{{ $eligibleLamarans->links() }}</div>
    </div>
</div>

<div class="card shadow"><div class="card-header d-flex flex-wrap justify-content-between align-items-center"><strong>Hasil kandidat</strong><form method="GET" action="{{ route('assessment-links.show', $link) }}" class="form-inline mt-2 mt-md-0"><label class="mr-2" for="eligibilityFilter">Kelayakan</label><select id="eligibilityFilter" name="eligibility" class="form-control form-control-sm" onchange="this.form.submit()"><option value="all" {{ $eligibility === 'all' ? 'selected' : '' }}>Semua</option><option value="eligible" {{ $eligibility === 'eligible' ? 'selected' : '' }}>Layak lanjut</option><option value="ineligible" {{ $eligibility === 'ineligible' ? 'selected' : '' }}>Tidak layak</option><option value="pending" {{ $eligibility === 'pending' ? 'selected' : '' }}>Menunggu hasil</option></select></form></div><div class="card-body"><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Kandidat</th><th>No. KTP</th><th>Posisi dilamar</th><th>Kelayakan</th><th>Hasil terbaru</th><th>Catatan</th><th>Terakhir dikirim</th><th>Audit</th></tr></thead><tbody>
@forelse($link->candidates as $candidate)
<tr><td>{{ optional(optional(optional($candidate->lamaran)->biodata)->user)->name ?: 'Kandidat #' . $candidate->lamaran_id }}</td><td>{{ optional(optional($candidate->lamaran)->biodata)->no_ktp ?: '-' }}</td><td>@php($lowongan = optional($candidate->lamaran)->lowongan)@if($lowongan){{ $lowongan->nama_lowongan }} - {{ $lowongan->created_at ? tanggalIndo($lowongan->created_at->toDateString()) : '-' }}@else-@endif</td><td>@if($candidate->eligibility_status === 'eligible')<span class="badge badge-success">Layak lanjut</span>@elseif($candidate->eligibility_status === 'ineligible')<span class="badge badge-danger">Tidak layak</span>@else<span class="badge badge-secondary">Menunggu hasil</span>@endif</td><td><pre class="small mb-0">{{ json_encode($candidate->result_values ?: [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></td><td>{{ $candidate->petugas_note ?: '-' }}</td><td>{{ optional($candidate->last_submitted_at)->format('d-m-Y H:i') ?: '-' }}</td><td>
@forelse($candidate->audits->sortByDesc('id') as $audit)
@php($oldAudit = (array) $audit->old_values)
@php($newAudit = (array) $audit->new_values)
@php($oldValues = array_key_exists('values', $oldAudit) ? $oldAudit['values'] : collect($oldAudit)->except('petugas_note')->all())
@php($newValues = array_key_exists('values', $newAudit) ? $newAudit['values'] : collect($newAudit)->except('petugas_note')->all())
<div class="border-bottom pb-2 mb-2 small"><strong>{{ optional($audit->created_at)->format('d-m-Y H:i') }}</strong><br>Nilai sebelumnya: {{ json_encode($oldValues, JSON_UNESCAPED_UNICODE) }}<br>Catatan sebelumnya: {{ data_get($oldAudit, 'petugas_note') ?: '-' }}<br>Nilai revisi: {{ json_encode($newValues, JSON_UNESCAPED_UNICODE) }}<br>Catatan revisi: {{ data_get($newAudit, 'petugas_note') ?: '-' }}</div>
@empty<span class="text-muted">Belum ada audit.</span>@endforelse
</td></tr>
@empty<tr><td colspan="8" class="text-center text-muted py-4">Belum ada kandidat pada link ini.</td></tr>@endforelse
</tbody></table></div></div></div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-copy-target]').forEach(function (button) { button.addEventListener('click', function () { var input = document.getElementById(button.dataset.copyTarget); if (navigator.clipboard) navigator.clipboard.writeText(input.value); input.select(); button.textContent = 'Tersalin'; }); });
var deactivateButton = document.querySelector('[data-deactivate-link]');
if (deactivateButton) {
    deactivateButton.addEventListener('click', function () {
        Swal.fire({ title: 'Nonaktifkan link?', text: 'Petugas tidak dapat lagi menggunakan link ini.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, nonaktifkan', cancelButtonText: 'Batal' }).then(function (result) {
            if (result.isConfirmed) document.getElementById('deactivateAssessmentLinkForm').submit();
        });
    });
}
</script>
@endpush
