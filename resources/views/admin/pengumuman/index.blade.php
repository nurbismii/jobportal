@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endpush

<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
    <h2 class="m-0 font-weight-bold text-primary">Pengumuman</h2>
    <a href="{{ route('pengumumans.create') }}" class="btn btn-primary btn-sm btn-icon-split">
        <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
        <span class="text">Pengumuman</span>
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Data Pengumuman</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover nowrap" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Pengumuman</th>
                        <th>Thumbnail</th>
                        <th>Tanggal Buat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pengumumans as $data)
                    <tr>
                        <td>{{ ++$no }}</td>
                        <td>{{ $data->pengumuman }}</td>
                        <td>
                            @if($data->thumbnail)
                            <img src="{{ asset('pengumuman/' . $data->thumbnail) }}" alt="Thumbnail" class="img-thumbnail" style="width: 100px; height: auto;">
                            @else
                            <span class="text-muted">Tidak ada thumbnail</span>
                            @endif
                        </td>
                        <td>{{ tanggalIndo($data->created_at) }}</td>
                        <td>
                            <a href="{{ route('pengumumans.edit', $data->id) }}" class="btn btn-success btn-sm btn-icon-split">
                                <span class="icon text-white-50">
                                    <i class="fas fa-pen"></i>
                                </span>
                                <span class="text">Edit</span>
                            </a>
                            <a href="{{ route('pengumumans.destroy', $data->id) }}" class="btn btn-danger btn-sm btn-icon-split" data-confirm-delete="true">
                                <span class="icon text-white-50">
                                    <i class="fas fa-trash"></i>
                                </span>
                                <span class="text">Hapus</span>
                            </a>
                        </td>
                    </tr>
                    @endforeach.
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