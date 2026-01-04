@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<!-- FixedColumns CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.dataTables.min.css">

<style>
    td.editable {
        cursor: text;
        background-color: #f9f9f9;
        border: 1px solid #ccc;
        padding: 6px 8px;
        border-radius: 4px;
        font-family: inherit;
        font-size: 14px;
        transition: all 0.2s ease-in-out;
    }

    td.editable:focus,
    td.editable[contenteditable="true"]:focus {
        outline: none;
        border: 1px solid #007bff;
        background-color: #fff;
        box-shadow: 0 0 3px rgba(0, 123, 255, 0.5);
    }

    td.editable[contenteditable="true"] {
        background-color: #f9f9f9;
    }

    td.editable.editing {
        background-color: #fffbe6;
        /* kuning soft saat sedang diedit */
    }

    /* Gaya tombol DataTables */
    .dt-button {
        margin-right: 5px;
        border-radius: 0.25rem;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }

    /* Dropdown Show entries */
    .dataTables_length select {
        width: auto;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        border: 1px solid #ced4da;
    }

    /* Search box styling */
    .dataTables_filter input {
        border-radius: 0.25rem;
        padding: 0.25rem 0.5rem;
        border: 1px solid #ced4da;
    }

    /* Pagination */
    .dataTables_paginate .pagination .page-item .page-link {
        border-radius: 0.25rem;
        /* margin: 0 1px; */
        color: #007bff;
    }

    .dataTables_paginate .pagination .page-item.active .page-link {
        background-color: #007bff;
        border-color: #007bff;
        color: #fff;
    }

    /* Hover row effect */
    #dataTable tbody tr:hover {
        background-color: #f1f3f5;
        cursor: pointer;
    }

    div.dataTables_wrapper div.dataTables_length {
        margin-right: 1rem;
        /* atau 16px, bisa ditambah sesuai kebutuhan */
    }

    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 52px;
        height: 26px;
    }

    .toggle-input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        inset: 0;
        background-color: #d1d5db;
        /* abu OFF */
        border-radius: 20px;
        transition: .3s;
    }

    .toggle-slider::before {
        content: "";
        position: absolute;
        height: 20px;
        width: 20px;
        left: 3px;
        bottom: 3px;
        background-color: #fff;
        border-radius: 50%;
        transition: .3s;
    }

    /* Text ON */
    .toggle-text {
        position: absolute;
        left: 9px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 11px;
        font-weight: 600;
        color: #fff;
        opacity: 0;
        transition: .3s;
    }

    /* ON state */
    .toggle-input:checked+.toggle-slider {
        background-color: #20c997;
        /* hijau */
    }

    .toggle-input:checked+.toggle-slider::before {
        transform: translateX(26px);
    }

    .toggle-input:checked+.toggle-slider .toggle-text {
        opacity: 1;
    }
</style>
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
                                    <th>Riwayat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($penggunas as $pengguna)
                                <tr>
                                    <td>{{ $pengguna->name }}</td>
                                    <td>{{ $pengguna->no_ktp }}</td>
                                    <td>{{ $pengguna->email }}</td>
                                    <td class="text-center">
                                        <label class="toggle-switch">
                                            <input
                                                type="checkbox"
                                                class="toggle-input toggle-status"
                                                data-id="{{ $pengguna->id }}"
                                                data-old="{{ $pengguna->status_akun }}"
                                                {{ $pengguna->status_akun == 1 ? 'checked' : '' }}>
                                            <span class="toggle-slider">
                                                <span class="toggle-text">ON</span>
                                            </span>
                                        </label>
                                    </td>
                                    <td>{{ $pengguna->biodataUser->getLatestRiwayatLamaran->status_proses ?? '-' }}</td>
                                    <td>{{ substr($pengguna->biodataUser->getLatestRiwayatLamaran->lowongan->nama_lowongan ?? '-', 0, 15) }}</td>
                                    <td class="editable"
                                        data-id="{{ $pengguna->id }}"
                                        data-model="user"
                                        data-field="rekomendasi">
                                        {{ $pengguna->rekomendasi}}
                                    </td>
                                    <td>
                                        <a href="{{ route('pengguna.show', $pengguna->id) }}" target="_blank" class="btn btn-secondary btn-sm btn-icon-split mr-2">
                                            <span class="icon text-white-50">
                                                <i class="fas fa-history"></i>
                                            </span>
                                            <span class="text">Riwayat</span>
                                        </a>
                                    </td>
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
<script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('change', function(e) {
        if (!e.target.classList.contains('toggle-status')) return;

        const toggle = e.target;
        const userId = toggle.dataset.id;
        const oldValue = toggle.dataset.old;
        const newValue = toggle.checked ? 1 : 0;

        Swal.fire({
            title: 'Ubah status akun?',
            text: newValue == 1 ? 'Akun akan diaktifkan' : 'Akun akan dinonaktifkan',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya',
            cancelButtonText: 'Batal'
        }).then((result) => {

            if (!result.isConfirmed) {
                toggle.checked = oldValue == 1;
                return;
            }

            fetch("{{ route('user.updateStatusAkun') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        id: userId,
                        status_akun: newValue
                    })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        toggle.dataset.old = newValue;
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            timer: 1200,
                            showConfirmButton: false
                        });
                    } else {
                        toggle.checked = oldValue == 1;
                    }
                })
                .catch(() => {
                    toggle.checked = oldValue == 1;
                });
        });
    });
</script>

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

<script>
    // Simpan nilai awal saat mulai edit
    $('#dataTable').on('focus', 'td.editable', function() {
        originalContent = $(this).text().trim();
    });

    // Aktifkan editable saat klik
    $('#dataTable').on('click', 'td.editable', function() {
        let $td = $(this);
        if (!$td.is('[contenteditable="true"]')) {
            $td.attr('contenteditable', 'true').focus();
        }
    });

    $('#dataTable').on('blur', 'td.editable', function() {
        let $td = $(this);
        $td.attr('contenteditable', 'false');

        const newValue = $td.text().trim();
        const debounceTimers = {};
        let originalContent = '';

        // ⛔ Jika tidak ada perubahan, tidak perlu update
        if (newValue === originalContent) {
            return;
        }

        const id = $td.data('id');
        const field = $td.data('field');
        const model = $td.data('model');
        const key = `${model}-${id}-${field}`;

        if (debounceTimers[key]) {
            clearTimeout(debounceTimers[key]);
        }

        debounceTimers[key] = setTimeout(() => {
            $.ajax({
                url: '{{ route("data.autoUpdate") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    model: model,
                    field: field,
                    value: newValue
                },
                success: function(res) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message || 'Rekomendasi telah diperbarui.',
                        timer: 2500,
                        toast: true,
                        showConfirmButton: false,
                        position: 'bottom-end'
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: xhr.responseJSON?.message || 'Terjadi kesalahan.',
                        timer: 3000,
                        toast: true,
                        showConfirmButton: false,
                        position: 'bottom-end'
                    });
                }
            });
        }, 1000);
    });

    // Tekan Enter untuk simpan cepat
    $('#dataTable').on('keydown', 'td.editable', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $(this).blur();
        }
    });
</script>
@endpush

@endsection