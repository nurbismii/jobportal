@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">

@endpush
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">
        Kandidat Potensial
        <a data-toggle="modal" data-target="#uploadSkillExp" class="btn btn-primary btn-sm btn-icon-split float-right">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text">Upload Kemampuan/Pengalaman</span>
        </a>
    </h1>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Data Kandidat</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered nowrap table-sm" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Lamaran Terakhir</th>
                                    <th>Status</th>
                                    <th>No HP</th>
                                    <th>Kemampuan/Pengalaman</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($kandidat_potensial as $kandidat)
                                <tr>
                                    <td>{{ $kandidat->user->name }}</td>
                                    <td>{{ $kandidat->user->email }}</td>
                                    <td>{{ $kandidat->getLatestRiwayatLamaran->lowongan->nama_lowongan}}</td>
                                    <td>{{ $kandidat->getLatestRiwayatLamaran->status_proses }}</td>
                                    <td>{{ $kandidat->no_telp }}</td>
                                    <td>{{ $kandidat->kemampuan_pengalaman }}</td>
                                    <td>
                                        <div class="d-flex">
                                            <a data-toggle="modal" data-target="#kandidat{{ $kandidat->id }}" class="btn btn-info btn-sm btn-icon-split mr-2">
                                                <span class="icon text-white-50">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                                <span class="text">Detail</span>
                                            </a>
                                            <a href="{{ route('kandidat-potensial.destroy', $kandidat->id) }}" class="btn btn-danger btn-sm btn-icon-split" data-confirm-delete="true">
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

<div class="modal fade" id="uploadSkillExp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">

            <!-- HEADER -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-file-excel me-2"></i> Import Data Excel
                </h5>
                <button type="button" class="btn-close btn-close-white" data-dismiss="modal"></button>
            </div>

            <!-- BODY -->
            <form action="{{ route('kandidat-potensial.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="modal-body">

                    <div class="alert alert-info d-flex align-items-center">
                        <i class="fas fa-info-circle me-2"></i>
                        Pastikan format excel sesuai template sistem.
                    </div>

                    <label class="fw-semibold mb-1">Pilih File Excel <span class="text-danger">*</span></label>
                    <div class="input-group mb-3">
                        <input type="file" name="file" id="fileExcel" class="form-control" accept=".xlsx, .xls" required>
                        <span class="input-group-text bg-success text-white">
                            <i class="fas fa-upload"></i>
                        </span>
                    </div>

                    <!-- Preview nama file -->
                    <small id="fileName" class="text-muted fst-italic">Belum ada file dipilih...</small>

                    <hr>
                </div>

                <!-- FOOTER -->
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Tutup
                    </button>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


@foreach($kandidat_potensial as $kandidat)
<div class="modal fade" id="kandidat{{ $kandidat->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">

            <!-- HEADER -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-tie me-2"></i> Detail Kandidat
                </h5>
                <button type="button" class="btn-close btn-close-white" data-dismiss="modal"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body">

                <!-- SECTION PROFILE -->
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">

                        <h5 class="text-primary fw-bold mb-3">
                            <i class="fas fa-id-card me-2"></i> Data Pribadi
                        </h5>

                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label class="text-muted fw-semibold">Nama Lengkap</label>
                                <div class="fw-bold">{{ $kandidat->user->name }}</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="text-muted fw-semibold">No. KTP</label>
                                <div class="fw-bold">{{ $kandidat->no_ktp }}</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="text-muted fw-semibold">Email</label>
                                <div class="fw-bold">{{ $kandidat->user->email }}</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="text-muted fw-semibold">No. HP</label>
                                <div class="fw-bold">{{ $kandidat->no_telp }}</div>
                            </div>

                            <div class="col-12">
                                <label class="text-muted fw-semibold">Kemampuan / Skill</label>
                                <div class="badge bg-info text-dark px-3 py-2">
                                    {{ $kandidat->kemampuan_pengalaman ?: 'Tidak ada informasi' }}
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- SECTION RIWAYAT -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="text-primary fw-bold mb-3">
                            <i class="fas fa-history me-2"></i> Riwayat Lamaran
                        </h5>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle">
                                <thead class="table-dark text-center">
                                    <tr>
                                        <th>Lowongan</th>
                                        <th>Status Proses</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($kandidat->getRiwayatLamaran as $lamaran)
                                    <tr>
                                        <td class="text-start">{{ $lamaran->lowongan->nama_lowongan }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary text-white px-3 py-2">
                                                {{ $lamaran->status_proses }}
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-3">
                                            <i class="fas fa-info-circle"></i> Belum ada riwayat lamaran.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

            </div>

            <!-- FOOTER -->
            <div class="modal-footer border-0">
                <button class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

@endforeach

@push('scripts')
<!-- Page level plugins -->
<script src="{{ asset('admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

<!-- Page level custom scripts -->
<script src="{{ asset('admin/js/demo/datatables-demo.js') }}"></script>

<script>
    document.getElementById('fileExcel').addEventListener('change', function() {
        let fileLabel = document.getElementById('fileName');
        fileLabel.textContent = this.files.length > 0 ? this.files[0].name : "Belum ada file dipilih...";
    });
</script>
@endpush

@endsection