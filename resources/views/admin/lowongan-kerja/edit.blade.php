@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
@endpush

<div class="container-fluid">

    <h3 class="h3 mb-3 text-gray-800">Edit Lowongan Kerja
        <a href="{{ route('lowongan.index') }}" class="btn btn-primary btn-sm btn-icon-split float-right">
            <span class="icon text-white-50"><i class="fas fa-arrow-left"></i></span>
            <span class="text">Kembali</span>
        </a>
    </h3>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Tambah Lowongan Kerja</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('lowongan.update', $lowongan->id) }}" method="POST">
                        @csrf
                        {{ method_field('patch') }}
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="nama-lowongan">Nama Lowongan Kerja
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="nama_lowongan" id="nama-lowongan" class="form-control" value="{{ $lowongan->nama_lowongan }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status-sim">Status SIM B2
                                    <span class="text-danger">*</span>
                                </label>
                                <select name="status_sim_b2" id="status-sim" class="form-control" required>
                                    <option value="{{ $lowongan->status_sim_b2 }}">{{ $lowongan->status_sim_b2 == '1' ? 'Dibutuhkan' : 'Tidak dibutuhkan' }}</option>
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
                                <input type="datetime-local" name="tanggal_mulai" id="tanggal-mulai" class="form-control" value="{{ $lowongan->tanggal_mulai }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tanggal-berakhir">Tanggal Berakhir
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="datetime-local" name="tanggal_berakhir" id="tanggal-berakhir" class="form-control" value="{{ $lowongan->tanggal_berakhir }}" required>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label" for="inputEmail">Kualifikasi
                                    <span class="text-danger">*</span>
                                </label>
                                <div id="quill-editor" class="mb-3" style="height: 300px;"></div>
                                <textarea rows="3" class="mb-3 d-none" name="kualifikasi" id="quill-editor-area">{{ $lowongan->kualifikasi }}</textarea>

                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary float-right">Simpan</button>
                    </form>
                </div>
            </div>
        </div>
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

            // Masukkan konten awal ke editor Quill
            editor.root.innerHTML = quillEditor.value;

            // Sync Quill ke textarea saat teks berubah
            editor.on('text-change', function() {
                quillEditor.value = editor.root.innerHTML;
            });
        }
    });
</script>
@endpush

@endsection