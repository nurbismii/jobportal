@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.dataTables.min.css">

<style>
    div.dataTables_wrapper div.dataTables_length {
        margin-right: 1rem;
        /* atau 16px, bisa ditambah sesuai kebutuhan */
    }
</style>
@endpush

<div class="container-fluid">
    <h1 class="h3 mb-3 text-gray-800">Personal File</h1>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Data Personal File</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered nowrap table-sm small" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>No KTP</th>
                                    <th>Surat Lamaran</th>
                                    <th>CV</th>
                                    <th>KTP</th>
                                    <th>SIM B2</th>
                                    <th>KK</th>
                                    <th>Ijazah</th>
                                    <th>SKCK</th>
                                    <th>AK1</th>
                                    <th>Vaksin</th>
                                    <th>NPWP</th>
                                    <th>Pas Foto</th>
                                    <th>Pendukung</th>
                                    <th>Zip</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="previewModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <div class="modal-header">
                <h5 id="previewTitle"></h5>
                <button class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body text-center">

                <img id="previewImage"
                    style="max-width:100%;display:none">

                <iframe id="previewPdf"
                    style="width:100%;height:80vh;border:none;display:none">
                </iframe>

            </div>

        </div>
    </div>
</div>

@push('scripts')
<!-- Page level plugins -->
<script src="{{ asset('admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

<!-- Buttons + Export + ColVis -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {

        const table = $('#dataTable').DataTable({
            processing: true,
            serverSide: true,
            scrollX: true,
            autoWidth: false,
            fixedHeader: true,

            ajax: "{{ route('personal-file.index') }}",


            dom: 'lBfrtip',
            lengthMenu: [
                [10, 50, 100, -1], // Nilai jumlah baris
                [10, 50, 100] // Label yang tampil di dropdown
            ],

            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'nama',
                    name: 'nama'
                },
                {
                    data: 'no_ktp',
                    name: 'no_ktp'
                },
                {
                    data: 'surat_lamaran',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'cv',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'ktp',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'sim_b_2',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'kk',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'ijazah',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'skck',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'ak1',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'vaksin',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'npwp',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'pas_foto',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'sertifikat_pendukung',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'download',
                    orderable: false,
                    searchable: false
                }
            ],
            buttons: [{
                extend: 'colvis',
                text: '<i class="fas fa-eye"></i> Visibility',
                className: 'btn btn-outline-primary btn-sm'
            }, ],
            fixedColumns: {
                leftColumns: 3
            }
        });
    });
</script>

<script>
    $(document).on('click', '.preview-file', function() {

        let file = $(this).data('file')
        let title = $(this).data('title')

        $('#previewTitle').text(title)

        let ext = file.split('.').pop().toLowerCase()

        $('#previewImage').hide()
        $('#previewPdf').hide()

        if (ext === 'pdf') {
            $('#previewPdf').attr('src', file).show()
        } else {
            $('#previewImage').attr('src', file).show()
        }

        $('#previewModal').modal('show')

    })
</script>
@endpush

@endsection