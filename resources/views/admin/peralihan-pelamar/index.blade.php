@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="{{ versioned_asset('admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
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
        $('#dataTable').DataTable({
            processing: true,
            serverSide: true,
            scrollX: true,
            autoWidth: false,
            order: [
                [1, 'asc']
            ],
            ajax: "{{ route('peralihan.index') }}",
            lengthMenu: [
                [10, 25, 50, 100],
                [10, 25, 50, 100]
            ],
            columns: [{
                    data: 'nama',
                    name: 'nama',
                    orderable: false
                },
                {
                    data: 'no_ktp',
                    name: 'biodata.no_ktp'
                },
                {
                    data: 'email',
                    name: 'email',
                    orderable: false
                },
                {
                    data: 'lamaran',
                    name: 'lamaran',
                    orderable: false
                },
                {
                    data: 'lamaran_lama',
                    name: 'lamaran_lama',
                    orderable: false
                },
                {
                    data: 'proses',
                    name: 'proses',
                    orderable: false
                },
                {
                    data: 'aksi',
                    orderable: false,
                    searchable: false
                }
            ]
        });
    });
</script>
@endpush

@endsection
