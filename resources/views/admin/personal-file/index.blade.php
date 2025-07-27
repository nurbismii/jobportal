@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
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
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($biodata as $bio)
                                <tr>
                                    <td>{{ ++$no }}</td>
                                    <td>{{ $bio->user->name ?? '-' }}</td>
                                    <td>{{ $bio->no_ktp }}</td>
                                    <td>
                                        <a href="{{ asset($bio->no_ktp . '/dokumen/' . $bio->surat_lamaran) }}" target="_blank">
                                            {{ $bio->surat_lamaran }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ asset($bio->no_ktp . '/dokumen/' . $bio->cv) }}" target="_blank">
                                            {{ $bio->cv }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ asset($bio->no_ktp . '/dokumen/' . $bio->ktp) }}" target="_blank">
                                            {{ $bio->ktp }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ asset($bio->no_ktp . '/dokumen/' . $bio->sim_b_2) }}" target="_blank">
                                            {{ $bio->sim_b_2 }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ asset($bio->no_ktp . '/dokumen/' . $bio->kk) }}" target="_blank">
                                            {{ $bio->kartu_keluarga }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ asset($bio->no_ktp . '/dokumen/' . $bio->ijazah) }}" target="_blank">
                                            {{ $bio->ijazah }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ asset($bio->no_ktp . '/dokumen/' . $bio->skck) }}" target="_blank">
                                            {{ $bio->skck }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ asset($bio->no_ktp . '/dokumen/' . $bio->ak1) }}" target="_blank">
                                            {{ $bio->ak1 }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ asset($bio->no_ktp . '/dokumen/' . $bio->vaksin) }}" target="_blank">
                                            {{ $bio->sertifikat_vaksin }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ asset($bio->no_ktp . '/dokumen/' . $bio->ijazah) }}" target="_blank">
                                            {{ $bio->npwp }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ asset($bio->no_ktp . '/dokumen/' . $bio->pas_foto) }}" target="_blank">
                                            {{ $bio->pas_foto }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ asset($bio->no_ktp . '/dokumen/' . $bio->sertifikat_pendukung) }}" target="_blank">
                                            {{ $bio->sertifikat_pendukung }}
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

<!-- Buttons + Export + ColVis -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        // Inisialisasi DataTable
        const table = $('#dataTable').DataTable({
            scrollX: true,
            responsive: false,
            autoWidth: false,
            fixedHeader: true,
            dom: 'lBfrtip',
            lengthMenu: [
                [10, 50, 100, -1], // Nilai jumlah baris
                [10, 50, 100, 'Semua'] // Label yang tampil di dropdown
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
@endpush

@endsection