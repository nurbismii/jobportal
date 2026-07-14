@extends('layouts.app-pic')

@section('content-admin')
<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <div><h2 class="m-0 font-weight-bold text-primary">Buat Link Asesmen</h2><div class="small text-muted">Pilih kandidat dari tahapan yang sesuai. PIN hanya dipakai untuk verifikasi dan tidak akan ditampilkan kembali.</div></div>
    <a href="{{ route('assessment-links.index') }}" class="btn btn-secondary btn-sm mt-2 mt-md-0">Kembali</a>
</div>

@if($errors->any())
<div class="alert alert-danger"><strong>Periksa kembali isian:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('assessment-links.store') }}" id="assessmentLinkForm">
@csrf
<div class="card shadow mb-3"><div class="card-body">
    <div class="row">
        <div class="col-md-6 mb-3"><label for="assessmentType">Tipe asesmen</label><select name="assessment_type" id="assessmentType" class="form-control" required><option value="lapangan" {{ old('assessment_type') === 'kesehatan' ? '' : 'selected' }}>Lapangan</option><option value="kesehatan" {{ old('assessment_type') === 'kesehatan' ? 'selected' : '' }}>Kesehatan</option></select></div>
        <div class="col-md-6 mb-3"><label>PIN (6–32 karakter)</label><input name="pin" type="password" class="form-control" minlength="6" maxlength="32" required autocomplete="new-password"></div>
    </div>
    <div id="healthNotice" class="alert alert-info mb-0 d-none">Hasil tes kesehatan standar (<strong>Sehat/Tidak Sehat</strong>) ditambahkan otomatis dan terkunci. Field tambahan di bawah bersifat pelengkap.</div>
</div></div>

<div class="card shadow mb-3"><div class="card-header d-flex flex-wrap align-items-center justify-content-between"><strong>Kandidat</strong><span id="selectedCount" class="badge badge-primary">0 dipilih</span></div><div class="card-body">
    <div class="row">
        <div class="col-md-4 form-group"><label for="candidateSearch">Cari kandidat</label><input id="candidateSearch" type="search" class="form-control" placeholder="Ketik nama atau nomor KTP"></div>
        <div class="col-md-4 form-group"><label for="lowonganFilter">Filter lowongan</label><select id="lowonganFilter" class="form-control"><option value="">Semua lowongan</option>@foreach($lowonganOptions as $lowongan)<option value="{{ $lowongan->id }}">{{ $lowongan->nama_lowongan }}</option>@endforeach</select></div>
        <div class="col-md-4 form-group"><label for="candidatePerPage">Tampilkan</label><select id="candidatePerPage" class="form-control"><option value="25" selected>25 kandidat</option><option value="50">50 kandidat</option><option value="100">100 kandidat</option><option value="all">Semua kandidat</option></select></div>
    </div>
    <div class="table-responsive"><table class="table table-bordered table-sm mb-0"><thead><tr><th><div class="custom-control custom-checkbox"><input id="selectVisibleCandidates" type="checkbox" class="custom-control-input" aria-label="Pilih semua kandidat pada halaman ini"><label class="custom-control-label" for="selectVisibleCandidates"></label></div></th><th>Nama</th><th>No. KTP</th><th>Posisi dilamar</th><th>Status proses</th></tr></thead><tbody id="candidateRows">
        @forelse($eligibleLamarans as $lamaran)
            @php $selected = in_array($lamaran->id, $selectedIds, true); @endphp
            <tr data-candidate-row data-assessment-type="{{ $lamaran->status_proses === 'Tes Kesehatan' ? 'kesehatan' : 'lapangan' }}" data-lowongan-id="{{ $lamaran->loker_id ?: '' }}" data-search="{{ optional(optional($lamaran->biodata)->user)->name }} {{ optional($lamaran->biodata)->no_ktp }}">
                <td><input type="checkbox" name="selected_ids[]" value="{{ $lamaran->id }}" {{ $selected ? 'checked' : '' }} aria-label="Pilih kandidat"></td>
                <td>{{ optional(optional($lamaran->biodata)->user)->name ?: 'Kandidat #'.$lamaran->id }}</td>
                <td>{{ optional($lamaran->biodata)->no_ktp ?: '-' }}</td>
                <td>{{ optional($lamaran->lowongan)->nama_lowongan ?: '-' }}</td>
                <td>{{ $lamaran->status_proses }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-muted text-center">Tidak ada kandidat pada tahapan tes.</td></tr>
        @endforelse
    </tbody></table></div>
    <div id="candidateEmpty" class="text-muted text-center py-3 d-none">Tidak ada kandidat yang sesuai pencarian atau tipe asesmen.</div>
    <nav id="candidatePagination" class="mt-3 d-none" aria-label="Navigasi kandidat"></nav>
</div></div>

<div class="card shadow"><div class="card-header d-flex justify-content-between align-items-center"><strong>Field tambahan</strong><button class="btn btn-outline-primary btn-sm" type="button" id="addField">Tambah field</button></div><div class="card-body"><div id="fields"></div><button class="btn btn-primary" type="submit" id="submitLink">Buat Link</button></div></div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    var fields = document.getElementById('fields'), index = 0, assessmentType = document.getElementById('assessmentType'), healthNotice = document.getElementById('healthNotice');
    var rows = Array.prototype.slice.call(document.querySelectorAll('[data-candidate-row]'));
    var search = document.getElementById('candidateSearch'), lowonganFilter = document.getElementById('lowonganFilter'), perPage = document.getElementById('candidatePerPage'), selectedCount = document.getElementById('selectedCount'), candidateEmpty = document.getElementById('candidateEmpty'), submitLink = document.getElementById('submitLink'), selectVisible = document.getElementById('selectVisibleCandidates'), pagination = document.getElementById('candidatePagination');
    var currentPage = 1;
    function matchingRows() {
        var query = search.value.toLowerCase().trim(), lowonganId = lowonganFilter.value;
        return rows.filter(function (row) {
            return row.dataset.assessmentType === assessmentType.value
                && (query === '' || row.dataset.search.toLowerCase().indexOf(query) !== -1)
                && (lowonganId === '' || row.dataset.lowonganId === lowonganId);
        });
    }
    function refreshCandidates(resetPage) {
        if (resetPage) currentPage = 1;
        var matches = matchingRows(), pageSize = perPage.value === 'all' ? matches.length || 1 : Number(perPage.value);
        var pageCount = Math.max(1, Math.ceil(matches.length / pageSize));
        if (currentPage > pageCount) currentPage = pageCount;
        var start = (currentPage - 1) * pageSize, visibleRows = matches.slice(start, start + pageSize), selected = 0;
        rows.forEach(function (row) {
            var checkbox = row.querySelector('input[type="checkbox"]');
            var isEligibleType = row.dataset.assessmentType === assessmentType.value;
            row.classList.toggle('d-none', visibleRows.indexOf(row) === -1);
            checkbox.disabled = !isEligibleType;
            if (!isEligibleType) checkbox.checked = false;
            if (checkbox.checked) selected++;
        });
        selectedCount.textContent = selected + ' dipilih';
        candidateEmpty.classList.toggle('d-none', matches.length > 0);
        submitLink.disabled = selected === 0;
        selectVisible.checked = visibleRows.length > 0 && visibleRows.every(function (row) { return row.querySelector('input[type="checkbox"]').checked; });
        selectVisible.indeterminate = visibleRows.some(function (row) { return row.querySelector('input[type="checkbox"]').checked; }) && !selectVisible.checked;
        pagination.classList.toggle('d-none', pageCount <= 1);
        pagination.innerHTML = pageCount <= 1 ? '' : '<ul class="pagination pagination-sm mb-0"><li class="page-item ' + (currentPage === 1 ? 'disabled' : '') + '"><button type="button" class="page-link" data-page="' + (currentPage - 1) + '">Sebelumnya</button></li><li class="page-item disabled"><span class="page-link">Halaman ' + currentPage + ' dari ' + pageCount + '</span></li><li class="page-item ' + (currentPage === pageCount ? 'disabled' : '') + '"><button type="button" class="page-link" data-page="' + (currentPage + 1) + '">Berikutnya</button></li></ul>';
    }
    function refreshHealthNotice() { healthNotice.classList.toggle('d-none', assessmentType.value !== 'kesehatan'); refreshCandidates(true); }
    function addField() {
        var i = index++;
        var row = document.createElement('div'); row.className = 'border rounded p-3 mb-3 field-row';
        row.innerHTML = '<div class="row"><div class="col-md-3 mb-2"><label>Label</label><input class="form-control form-control-sm" name="fields[' + i + '][label]" required></div><div class="col-md-3 mb-2"><label>ID field</label><input class="form-control form-control-sm" name="fields[' + i + '][id]" required></div><div class="col-md-2 mb-2"><label>Tipe</label><select class="form-control form-control-sm field-type" name="fields[' + i + '][type]"><option value="text">Teks</option><option value="number">Angka</option><option value="select">Pilihan</option></select></div><div class="col-md-2 mb-2 pt-md-4"><div class="form-check"><input type="hidden" name="fields[' + i + '][required]" value="0"><input class="form-check-input" type="checkbox" name="fields[' + i + '][required]" value="1" id="required' + i + '"><label class="form-check-label" for="required' + i + '">Wajib diisi</label></div></div><div class="col-md-2 mb-2 pt-md-4"><button class="btn btn-outline-danger btn-sm remove-field" type="button">Hapus</button></div></div><div class="field-options d-none"><label>Pilihan (satu per baris)</label><textarea class="form-control form-control-sm" rows="3" placeholder="Contoh pilihan 1&#10;Contoh pilihan 2"></textarea></div>';
        row.querySelector('.remove-field').addEventListener('click', function () { row.remove(); });
        row.querySelector('.field-type').addEventListener('change', function (event) { row.querySelector('.field-options').classList.toggle('d-none', event.target.value !== 'select'); });
        row.querySelector('.field-options textarea').addEventListener('input', function (event) { var existing = row.querySelectorAll('.option-input'); existing.forEach(function (input) { input.remove(); }); event.target.value.split(/\r?\n/).filter(Boolean).forEach(function (option, optionIndex) { var input = document.createElement('input'); input.type = 'hidden'; input.className = 'option-input'; input.name = 'fields[' + i + '][options][' + optionIndex + ']'; input.value = option.trim(); row.appendChild(input); }); });
        fields.appendChild(row);
    }
    document.getElementById('addField').addEventListener('click', addField);
    assessmentType.addEventListener('change', refreshHealthNotice);
    search.addEventListener('input', function () { refreshCandidates(true); });
    lowonganFilter.addEventListener('change', function () { refreshCandidates(true); });
    perPage.addEventListener('change', function () { refreshCandidates(true); });
    rows.forEach(function (row) { row.querySelector('input[type="checkbox"]').addEventListener('change', refreshCandidates); });
    selectVisible.addEventListener('change', function () { matchingRows().slice((currentPage - 1) * (perPage.value === 'all' ? matchingRows().length || 1 : Number(perPage.value)), currentPage * (perPage.value === 'all' ? matchingRows().length || 1 : Number(perPage.value))).forEach(function (row) { row.querySelector('input[type="checkbox"]').checked = selectVisible.checked; }); refreshCandidates(false); });
    pagination.addEventListener('click', function (event) { var button = event.target.closest('[data-page]'); if (!button || button.closest('.disabled')) return; currentPage = Number(button.dataset.page); refreshCandidates(false); });
    refreshHealthNotice();
})();
</script>
@endpush
