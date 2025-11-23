@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endpush
<div class="container-fluid">

    <h1 class="h3 mb-3 text-gray-800">Permintaan Tenaga Kerja
        <a href="{{ route('permintaan-tenaga-kerja.create') }}" class="btn btn-primary btn-sm btn-icon-split float-right">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text">Permintaan Tenaga Kerja</span>
        </a>
    </h1>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Data Permintaan Tenaga Kerja</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover nowrap" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>No Surat PTK</th>
                                    <th>Departemen</th>
                                    <th>Divisi</th>
                                    <th>Posisi</th>
                                    <th>Status PTK</th>
                                    <th>Jumlah Permintaan</th>
                                    <th>Sudah Masuk</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($permintaanTenagaKerjas as $index => $permintaan)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $permintaan->no_surat_ptk }}</td>
                                    <td>{{ $permintaan->departemen->departemen ?? 'N/A' }}</td>
                                    <td>{{ $permintaan->divisi->nama_divisi ?? 'N/A' }}</td>
                                    <td>{{ $permintaan->posisi }}</td>
                                    <td class="text-center">
                                        @php
                                        $status = strtolower($permintaan->status_ptk);
                                        $badgeClass = [
                                        'diterima' => 'success',
                                        'ditolak' => 'danger',
                                        'menunggu' => 'secondary',
                                        'proses' => 'warning',
                                        'selesai' => 'primary',
                                        ][$status] ?? 'light';
                                        @endphp
                                        <span class="badge badge-{{ $badgeClass }}">
                                            {{ ucfirst($permintaan->status_ptk) }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $permintaan->jumlah_ptk }}</td>
                                    <td class="text-center">{{ $permintaan->jumlah_masuk }}</td>
                                    <td>
                                        <a href="{{ route('permintaan-tenaga-kerja.show', $permintaan->id) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                        <a href="{{ route('permintaan-tenaga-kerja.edit', $permintaan->id) }}" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<!-- Page level plugins -->
<script src="{{ asset('admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

<script>
    $(document).ready(function() {
        // Inisialisasi DataTable
        const table = $('#dataTable').DataTable({
            scrollX: true,
            responsive: false,
            autoWidth: false,
            fixedHeader: true,
        });
    });
</script>
@endpush

@endsection