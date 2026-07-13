@extends('layouts.app-pic')

@section('content-admin')
<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <div><h2 class="m-0 font-weight-bold text-primary">Buat Link Asesmen</h2><div class="small text-muted">PIN hanya dipakai untuk verifikasi dan tidak akan ditampilkan kembali.</div></div>
    <a href="{{ route('assessment-links.index') }}" class="btn btn-secondary btn-sm mt-2 mt-md-0">Kembali</a>
</div>

@if($errors->any())
<div class="alert alert-danger"><strong>Periksa kembali isian:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('assessment-links.store') }}" id="assessmentLinkForm">
@csrf
<div class="card shadow mb-3"><div class="card-body">
    <div class="row">
        <div class="col-md-6 mb-3"><label>Tipe asesmen</label><select name="assessment_type" id="assessmentType" class="form-control" required><option value="lapangan" {{ old('assessment_type') === 'lapangan' ? 'selected' : '' }}>Lapangan</option><option value="kesehatan" {{ old('assessment_type') === 'kesehatan' ? 'selected' : '' }}>Kesehatan</option></select></div>
        <div class="col-md-6 mb-3"><label>PIN (6–32 karakter)</label><input name="pin" type="password" class="form-control" minlength="6" maxlength="32" required autocomplete="new-password"></div>
    </div>
    <div id="healthNotice" class="alert alert-info mb-0 d-none">Hasil tes kesehatan standar (<strong>Sehat/Tidak Sehat</strong>) ditambahkan otomatis dan terkunci. Field tambahan di bawah bersifat pelengkap.</div>
</div></div>

<div class="card shadow mb-3"><div class="card-header"><strong>Kandidat dipilih ({{ $lamarans->count() }})</strong></div><div class="card-body">
    @forelse($lamarans as $lamaran)
        <input type="hidden" name="selected_ids[]" value="{{ $lamaran->id }}">
        <div class="border-bottom py-2">{{ optional(optional($lamaran->biodata)->user)->name ?: 'Kandidat #' . $lamaran->id }} <span class="small text-muted">(Lamaran #{{ $lamaran->id }})</span></div>
    @empty
        <div class="text-muted">Belum ada kandidat dipilih. Kembali ke daftar pelamar untuk memilih kandidat.</div>
    @endforelse
</div></div>

<div class="card shadow"><div class="card-header d-flex justify-content-between align-items-center"><strong>Field tambahan</strong><button class="btn btn-outline-primary btn-sm" type="button" id="addField">Tambah field</button></div><div class="card-body"><div id="fields"></div><button class="btn btn-primary" type="submit" {{ $lamarans->isEmpty() ? 'disabled' : '' }}>Buat Link</button></div></div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    var fields = document.getElementById('fields'), index = 0, assessmentType = document.getElementById('assessmentType'), healthNotice = document.getElementById('healthNotice');
    function refreshHealthNotice() { healthNotice.classList.toggle('d-none', assessmentType.value !== 'kesehatan'); }
    function addField() {
        var i = index++;
        var row = document.createElement('div'); row.className = 'border rounded p-3 mb-3 field-row';
        row.innerHTML = '<div class="row"><div class="col-md-3 mb-2"><label>Label</label><input class="form-control form-control-sm" name="fields[' + i + '][label]" required></div><div class="col-md-3 mb-2"><label>ID field</label><input class="form-control form-control-sm" name="fields[' + i + '][id]" required></div><div class="col-md-2 mb-2"><label>Tipe</label><select class="form-control form-control-sm field-type" name="fields[' + i + '][type]"><option value="text">Teks</option><option value="number">Angka</option><option value="select">Pilihan</option></select></div><div class="col-md-2 mb-2 pt-md-4"><div class="form-check"><input type="hidden" name="fields[' + i + '][required]" value="0"><input class="form-check-input" type="checkbox" name="fields[' + i + '][required]" value="1" id="required' + i + '"><label class="form-check-label" for="required' + i + '">Wajib diisi</label></div></div><div class="col-md-2 mb-2 pt-md-4"><button class="btn btn-outline-danger btn-sm remove-field" type="button">Hapus</button></div></div><div class="field-options d-none"><label>Pilihan (satu per baris)</label><textarea class="form-control form-control-sm" rows="3" placeholder="Contoh pilihan 1&#10;Contoh pilihan 2"></textarea></div>';
        row.querySelector('.remove-field').addEventListener('click', function () { row.remove(); });
        row.querySelector('.field-type').addEventListener('change', function (event) { var options = row.querySelector('.field-options'); options.classList.toggle('d-none', event.target.value !== 'select'); });
        row.querySelector('.field-options textarea').addEventListener('input', function (event) { var existing = row.querySelectorAll('.option-input'); existing.forEach(function (input) { input.remove(); }); event.target.value.split(/\r?\n/).filter(Boolean).forEach(function (option, optionIndex) { var input = document.createElement('input'); input.type = 'hidden'; input.className = 'option-input'; input.name = 'fields[' + i + '][options][' + optionIndex + ']'; input.value = option.trim(); row.appendChild(input); }); });
        fields.appendChild(row);
    }
    document.getElementById('addField').addEventListener('click', addField); assessmentType.addEventListener('change', refreshHealthNotice); refreshHealthNotice();
})();
</script>
@endpush
