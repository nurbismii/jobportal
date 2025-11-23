@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

<div class="container-fluid">

    <h3 class="h3 mb-3 text-gray-800">Edit Permintaan Tenaga Kerja
        <a href="{{ route('permintaan-tenaga-kerja.index') }}" class="btn btn-primary btn-sm btn-icon-split float-right">
            <span class="icon text-white-50"><i class="fas fa-arrow-left"></i></span>
            <span class="text">Kembali</span>
        </a>
    </h3>


    <div class="row mb-3">
        <div class="col-12">

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Form Edit Permintaan Tenaga Kerja</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('permintaan-tenaga-kerja.update', $permintaanTenagaKerja->id) }}" method="POST">
                        @csrf
                        {{ method_field('patch') }}
                        <div class="row g-3">
                            <div class="col-md-12 mb-3">
                                <label for="no-surat-permintaan">No Surat Permintaan Tenaga Kerja
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="no_surat_permintaan" class="form-control" id="no-surat-permintaan" value="{{ $permintaanTenagaKerja->no_surat_ptk }}" required>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="departemen">Departemen <span class="text-danger">*</span></label>
                                <select name="departemen" class="form-control" id="departemen">
                                    <option value="{{ $permintaanTenagaKerja->departemen->id }}">{{ $permintaanTenagaKerja->departemen->departemen }}</option>

                                    @php
                                    $grouped = $departemens->groupBy('perusahaan_id');
                                    $namaPerusahaan = [
                                    '1' => 'PT Virtue Dragon Nickel Industry',
                                    '2' => 'PT Virtue Dragon Nickel Industrial Park'
                                    ];
                                    @endphp

                                    @foreach ($grouped as $perusahaanId => $departemensPerusahaan)
                                    <optgroup label="{{ $namaPerusahaan[$perusahaanId] ?? 'Perusahaan Lain' }}">
                                        @foreach ($departemensPerusahaan as $departemen)
                                        <option value="{{ $departemen->id }}">
                                            {{ $departemen->departemen }}
                                        </option>
                                        @endforeach
                                    </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="divisi">Divisi
                                    <span class="text-danger">*</span>
                                </label>
                                <select name="divisi" class="form-control" id="divisi">
                                    <option value="{{ $permintaanTenagaKerja->divisi->id }}">{{ $permintaanTenagaKerja->divisi->nama_divisi }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="posisi">Posisi
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="posisi" id="posisi" class="form-control" value="{{ $permintaanTenagaKerja->posisi }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="jumlah-permintaan">Jumlah Permintaan Tenaga Kerja
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="jumlah_ptk" class="form-control" id="jumlah-permintaan" value="{{ $permintaanTenagaKerja->jumlah_ptk }}" required>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="tanggal-pengajuan">Tanggal Pengajuan
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="tanggal_pengajuan" id="tanggal-pengajuan" value="{{ $permintaanTenagaKerja->tanggal_pengajuan }}" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tanggal-diterima">Tanggal Diterima
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="tanggal_terima" class="form-control" id="tanggal-diterima" value="{{ $permintaanTenagaKerja->tanggal_terima }}" required>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="jenis-kelamin">Jenis Kelamin
                                    <span class="text-danger">*</span>
                                </label>
                                <select name="jenis_kelamin" id="jenis-kelamin" class="form-control" required>
                                    @php
                                    $jenisKelaminOptions = [
                                    'Laki-laki' => 'Laki-laki',
                                    'Perempuan' => 'Perempuan',
                                    'Laki-laki dan Perempuan' => 'Laki-laki dan Perempuan'
                                    ];
                                    @endphp
                                    @foreach($jenisKelaminOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $permintaanTenagaKerja->jenis_kelamin == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="usia">Usia
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="rentang_usia" class="form-control" id="usia" value="{{ $permintaanTenagaKerja->rentang_usia }}" required>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="background-pendidikan">Background Pendidikan
                                    <span class="text-danger">*</span>
                                </label>
                                <select type="text" name="background_pendidikan" id="background-pendidikan" class="form-control" required>
                                    @php
                                    $pendidikanOptions = ['SMA/SMK', 'D3', 'S1', 'S2', 'S3'];
                                    @endphp
                                    <option value="">-- Pilih background pendidikan --</option>
                                    @foreach($pendidikanOptions as $option)
                                    <option value="{{ $option }}" {{ $permintaanTenagaKerja->background_pendidikan == $option ? 'selected' : '' }}>
                                        {{ $option }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status-ptk">Status Permintaan Tenaga Kerja
                                    <span class="text-danger">*</span>
                                </label>
                                <select name="status_ptk" id="status-ptk" class="form-control" required>
                                    @php
                                    $statusOptions = ['Diterima', 'Ditolak', 'Menunggu', 'Proses', 'Selesai'];
                                    @endphp
                                    @foreach($statusOptions as $option)
                                    <option value="{{ $option }}" {{ $permintaanTenagaKerja->status_ptk == $option ? 'selected' : '' }}>
                                        {{ $option }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-12 mb-3">
                                <label class="form-label" for="quill-editor-area">Kualifikasi Permintaan Tenaga Kerja
                                    <span class="text-danger">*</span>
                                </label>
                                <div id="quill-editor" class="mb-3" style="height: 300px;"></div>
                                <textarea rows="3" class="mb-3 d-none" name="kualifikasi_ptk" id="quill-editor-area" required></textarea>

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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Initialize Quill editor -->
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('quill-editor-area')) {
            var editor = new Quill('#quill-editor', {
                theme: 'snow'
            });
            var quillEditor = document.getElementById('quill-editor-area');
            // Set initial content from textarea (for edit)
            editor.root.innerHTML = quillEditor.value = @json($permintaanTenagaKerja->kualifikasi_ptk);

            editor.on('text-change', function() {
                quillEditor.value = editor.root.innerHTML;
            });

            quillEditor.addEventListener('input', function() {
                editor.root.innerHTML = quillEditor.value;
            });
        }
    });

    $(document).ready(function() {
        $('#departemen').change(function() {
            var departemenID = $(this).val();
            $('#divisi').html('<option value="">-- Pilih divisi --</option>'); // reset divisi

            if (departemenID) {
                $.ajax({
                    url: '/api/get-divisi/' + departemenID,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (data.length > 0) {
                            $.each(data, function(key, value) {
                                $('#divisi').append('<option value="' + value.id + '">' + value.nama_divisi + '</option>');
                            });
                        }
                    }
                });
            }
        });
    });

    $(document).ready(function() {
        $('.departemen').select2();
    });
</script>
@endpush

@endsection