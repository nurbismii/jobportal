@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

<div class="container-fluid">

    <h3 class="h3 mb-3 text-gray-800">Edit Permintaan Tenaga Kerja
        @if(auth()->user()->canAccessAdminRoute('permintaan-tenaga-kerja.index'))
        <a href="{{ route('permintaan-tenaga-kerja.index') }}" class="btn btn-primary btn-sm btn-icon-split float-right">
            <span class="icon text-white-50"><i class="fas fa-arrow-left"></i></span>
            <span class="text">Kembali</span>
        </a>
        @endif
    </h3>


    <div class="row mb-3">
        <div class="col-12">

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Form Edit Permintaan Tenaga Kerja</h6>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <form action="{{ route('permintaan-tenaga-kerja.update', $permintaanTenagaKerja->id) }}" method="POST" data-permission-allowed="{{ auth()->user()->canAccessAdminRoute('permintaan-tenaga-kerja.update') ? '1' : '0' }}">
                        @csrf
                        {{ method_field('patch') }}
                        <div class="row g-3">
                            <div class="col-md-12 mb-3">
                                <label for="no-surat-permintaan">No Surat Permintaan Tenaga Kerja
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="no_surat_permintaan" class="form-control" id="no-surat-permintaan" value="{{ old('no_surat_permintaan', $permintaanTenagaKerja->no_surat_ptk) }}" required>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="departemen">Departemen <span class="text-danger">*</span></label>
                                <select name="departemen" class="form-control departemen" id="departemen" required>
                                    <option value="">-- Pilih departemen --</option>

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
                                        <option value="{{ $departemen->id }}" {{ (string) old('departemen', $permintaanTenagaKerja->departemen_id) === (string) $departemen->id ? 'selected' : '' }}>
                                            {{ $departemen->departemen }}
                                        </option>
                                        @endforeach
                                    </optgroup>
                                    @endforeach
                                </select>
                            </div>

                        </div>

                        @include('admin.permintaan-tenaga-kerja._rincian')

                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="tanggal-pengajuan">Tanggal Pengajuan
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="tanggal_pengajuan" id="tanggal-pengajuan" value="{{ old('tanggal_pengajuan', $permintaanTenagaKerja->tanggal_pengajuan) }}" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tanggal-diterima">Tanggal Diterima
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="tanggal_terima" class="form-control" id="tanggal-diterima" value="{{ old('tanggal_terima', $permintaanTenagaKerja->tanggal_terima) }}" required>
                            </div>
                        </div>

                        <div class="row g-3">

                            <div class="col-md-6 mb-3">
                                <label for="status-ptk">Status Permintaan Tenaga Kerja
                                    <span class="text-danger">*</span>
                                </label>
                                <select name="status_ptk" id="status-ptk" class="form-control" required>
                                    @php
                                    $statusOptions = ['Diterima', 'Ditolak', 'Menunggu', 'Proses', 'Selesai'];
                                    @endphp
                                    @foreach($statusOptions as $option)
                                    <option value="{{ $option }}" {{ old('status_ptk', $permintaanTenagaKerja->status_ptk) == $option ? 'selected' : '' }}>
                                        {{ $option }}
                                    </option>
                                    @endforeach
                                </select>
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


<script type="text/javascript">
    $(document).ready(function() {
        $('.departemen').select2();
    });
</script>
@endpush

@endsection
