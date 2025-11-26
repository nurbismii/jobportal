@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endpush
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Pengguna</h1>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Data Pengguna</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered nowrap table-sm" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>KTP</th>
                                    <th>Email</th>
                                    <th>Akun</th>
                                    <th>Lamaran</th>
                                    <th>Lowongan</th>
                                    <th>Rekomendasi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($penggunas as $pengguna)
                                <tr>
                                    <td>{{ $pengguna->name }}</td>
                                    <td>{{ $pengguna->no_ktp }}</td>
                                    <td>{{ $pengguna->email }}</td>
                                    <td>
                                        @if($pengguna->status_akun == '1')
                                        <span class="badge badge-success">AKTIF</span>
                                        @else
                                        <span class="badge badge-danger">TIDAK AKTIF</span>
                                        @endif
                                    </td>
                                    <td>{{ $pengguna->biodataUser->getLatestRiwayatLamaran->status_proses ?? '-' }}</td>
                                    <td>{{ substr($pengguna->biodataUser->getLatestRiwayatLamaran->lowongan->nama_lowongan ?? '-', 0, 15) }}</td>
                                    <td></td>
                                    <td>
                                        <div class="d-flex">
                                            <a href="{{ route('pengguna.edit', $pengguna->id) }}" class="btn btn-success btn-sm btn-icon-split mr-2">
                                                <span class="icon text-white-50">
                                                    <i class="fas fa-pen"></i>
                                                </span>
                                                <span class="text">Edit</span>
                                            </a>
                                            <a href="{{ route('pengguna.destroy', $pengguna->id) }}" class="btn btn-danger btn-sm btn-icon-split" data-confirm-delete="true">
                                                <span class="icon text-white-50">
                                                    <i class="fas fa-trash"></i>
                                                </span>
                                                <span class="text">Hapus</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                                <!-- Add more rows as needed -->
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

<!-- Page level custom scripts -->
<script src="{{ asset('admin/js/demo/datatables-demo.js') }}"></script>
@endpush

@endsection