@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
    <h6 class="m-0 font-weight-bold text-primary">Tambah lowongan</h6>
    <a href="{{ route('lowongan.index') }}" class="btn btn-sm btn-primary">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="col-md-12 mt-2">
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Perhatian!</strong> Pastikan data permintaan tenaga kerja sudah lengkap sebelum membuat lowongan kerja.
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
</div>

<form action="{{ route('lowongan.store') }}" method="POST">
    @csrf
    <div class="card shadow mb-3">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Data Permintaan Tenaga Kerja</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-12 mb-3">
                    <label for="ptk">Pilih Permintaan Tenaga Kerja
                        <span class="text-danger">*</span>
                    </label>
                    <select class="form-control ptk-select" name="ptk_id" id="ptk_id" required>
                        <option value="">Pilih PTK</option>
                        @php
                        $groupedPtk = $permintaanTenagaKerjas->groupBy('departemen_id');
                        @endphp
                        @foreach($groupedPtk as $deptId => $items)
                        <optgroup label="{{ $items->first()->departemen->departemen }}">
                            @foreach($items as $ptk)
                            <option
                                value="{{ $ptk->id }}"
                                data-no_surat="{{ $ptk->no_surat_ptk }}"
                                data-departemen="{{ $ptk->departemen->departemen }}"
                                data-divisi="{{ $ptk->divisi->nama_divisi }}"
                                data-posisi="{{ $ptk->posisi }}"
                                data-jumlah="{{ $ptk->jumlah_ptk }}"
                                data-jumlah_masuk="{{ $ptk->jumlah_masuk }}"
                                data-status="{{ $ptk->status_ptk }}">
                                {{ $ptk->no_surat_ptk }} - {{ $ptk->posisi }}
                            </option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-12 mb-3">
                    <div id="laporan-singkat" class="card border-primary d-none">
                        <div class="card-header bg-primary text-white py-2">
                            <strong><i class="fas fa-info-circle"></i> Rangkuman Permintaan Tenaga Kerja</strong>
                        </div>
                        <div class="card-body py-2">
                            <div class="row mb-2">
                                <div class="col-5 font-weight-bold">No Surat</div>
                                <div class="col-7" id="laporan-no-surat"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 font-weight-bold">Departemen</div>
                                <div class="col-7" id="laporan-departemen"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 font-weight-bold">Divisi</div>
                                <div class="col-7" id="laporan-divisi"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 font-weight-bold">Posisi</div>
                                <div class="col-7" id="laporan-posisi"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 font-weight-bold">Jumlah Permintaan</div>
                                <div class="col-7" id="laporan-jumlah-permintaan"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 font-weight-bold">Jumlah Terpenuhi</div>
                                <div class="col-7" id="laporan-jumlah-terpenuhi"></div>
                            </div>
                            <div class="row">
                                <div class="col-5 font-weight-bold">Status</div>
                                <div class="col-7" id="laporan-status"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Form Lowongan Kerja</h6>
        </div>
        <div class="card-body">

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

        </div>
    </div>
</form>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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

    $(document).ready(function() {
        $('.ptk-select').select2({
            placeholder: 'Pilih PTK',
            allowClear: true,
            width: "100%",
        });

        $('.ptk-select').change(function() {
            var selected = $(this).find(':selected');

            if (selected.val()) {
                $('#laporan-no-surat').text(selected.data('no_surat'));
                $('#laporan-departemen').text(selected.data('departemen'));
                $('#laporan-divisi').text(selected.data('divisi'));
                $('#laporan-posisi').text(selected.data('posisi'));
                $('#laporan-jumlah-permintaan').text(selected.data('jumlah'));
                $('#laporan-jumlah-terpenuhi').text(selected.data('jumlah_masuk'));
                $('#laporan-status').text(selected.data('status'));
                $('#laporan-singkat').removeClass('d-none');
            } else {
                $('#laporan-singkat').addClass('d-none');
            }
        });
    });
</script>
@endpush

@endsection