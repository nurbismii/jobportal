@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endpush
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Peralihan Pelamar</h1>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Data Pelamar</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered nowrap table-sm" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>KTP</th>
                                    <th>Email</th>
                                    <th>Lamaran</th>
                                    <th>Lamaran Lama</th>
                                    <th>Proses</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($biodatas as $data)
                                <tr>
                                    <td>{{ $data->user->name ?? '-' }}</td>
                                    <td>{{ $data->no_ktp }}</td>
                                    <td>{{ $data->user->email ?? '-' }}</td>
                                    <td>{{ $data->getLatestRiwayatLamaran->lowongan->nama_lowongan ?? '-' }}</td>
                                    <td>{{ getLamaranLama($data->getLatestRiwayatLamaran->loker_id_lama ?? null) }}</td>
                                    <td>{{ $data->getLatestRiwayatLamaran->status_proses ?? '-' }}</td>
                                    <td>
                                        <div class="d-flex justify-content-center">
                                            <a href="{{ route('peralihan.edit', $data->id) }}" class="btn btn-success btn-sm btn-icon-split">
                                                <span class="icon text-white-50">
                                                    <i class="fas fa-pen"></i>
                                                </span>
                                                <span class="text">Alihkan</span>
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