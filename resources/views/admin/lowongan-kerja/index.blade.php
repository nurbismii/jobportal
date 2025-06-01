@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endpush

<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
    <h6 class="m-0 font-weight-bold text-primary"></h6>
    <a href="{{ route('lowongan.create') }}" class="btn btn-primary btn-sm btn-icon-split" data-confirm-delete="true">
        <span class="icon text-white-50">
            <i class="fas fa-plus"></i>
        </span>
        <span class="text">Lowongan</span>
    </a>
</div>
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Data Lowongan</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Lowongan</th>
                        <th>Sim B2</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Berakhir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lowongans as $data)
                    <tr>
                        <td>{{ ++$no }}</td>
                        <td>{{ $data->nama_lowongan }}</td>
                        <td>{{ $data->status_sim_b2 == 1 ? 'Dibutuhkan' : 'Tidak dibutuhkan' }}</td>
                        <td>{{ $data->tanggal_mulai }}</td>
                        <td>{{ $data->tanggal_berakhir }}</td>
                        <td>
                            <a href="{{ route('lowongan.edit', $data->id) }}" class="btn btn-success btn-sm btn-icon-split">
                                <span class="icon text-white-50">
                                    <i class="fas fa-pen"></i>
                                </span>
                                <span class="text">Edit</span>
                            </a>
                            <div class="my-2"></div>
                            <a href="{{ route('lowongan.destroy', $data->id) }}" class="btn btn-danger btn-sm btn-icon-split" data-confirm-delete="true">
                                <span class="icon text-white-50">
                                    <i class="fas fa-trash"></i>
                                </span>
                                <span class="text">Hapus</span>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
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