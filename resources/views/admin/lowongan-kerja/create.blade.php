@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
@endpush
<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
    <h6 class="m-0 font-weight-bold text-primary">Tambah lowongan</h6>
    <a href="{{ route('lowongan.index') }}" class="btn btn-sm btn-primary">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>


<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Tambah Lowongan Kerja</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('lowongan.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6 mb-3">
                    <label for="nama-lowongan">Nama Lowongan Kerja
                        <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="nama_lowongan" id="nama-lowongan" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="status-sim">Status SIM B2
                        <span class="text-danger">*</span>
                    </label>
                    <select name="status_sim_b2" id="status-sim" class="form-control" required>
                        <option value="">Pilih status sim B2</option>
                        <option value="1">Dibutuhkan</option>
                        <option value="0">Tidak dibutuhkan</option>
                    </select>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6 mb-3">
                    <label for="tanggal-mulai">Tanggal Mulai
                        <span class="text-danger">*</span>
                    </label>
                    <input type="datetime-local" name="tanggal_mulai" id="tanggal-mulai" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="tanggal-berakhir">Tanggal Berakhir
                        <span class="text-danger">*</span>
                    </label>
                    <input type="datetime-local" name="tanggal_berakhir" id="tanggal-berakhir" class="form-control" required>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label" for="inputEmail">Kualifikasi
                        <span class="text-danger">*</span>
                    </label>
                    <div id="quill-editor" class="mb-3" style="height: 300px;"></div>
                    <textarea rows="3" class="mb-3 d-none" name="kualifikasi" id="quill-editor-area"></textarea>

                </div>
            </div>
            <button type="submit" class="btn btn-primary float-right">Simpan</button>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>

<!-- Initialize Quill editor -->
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('quill-editor-area')) {
            var editor = new Quill('#quill-editor', {
                theme: 'snow'
            });
            var quillEditor = document.getElementById('quill-editor-area');
            editor.on('text-change', function() {
                quillEditor.value = editor.root.innerHTML;
            });

            quillEditor.addEventListener('input', function() {
                editor.root.innerHTML = quillEditor.value;
            });
        }
    });
</script>
@endpush

@endsection