@extends('layouts.app')

@section('content')
<div class="container py-5">

    @if($msg_name_ktp_vs_sim_b2 || $msg_date_ktp_vs_sim_b2)
    <div class="alert border-1 border-danger shadow-sm rounded-3 p-4">
        <div class="d-flex align-items-start">
            <div class="me-3">
                <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
            </div>
            <div>
                <h4 class="mb-2 text-dark fw-bold">Pemeriksaan OCR! (Optical Character Recognition)</h4>
                <p class="mb-3 text-danger fw-bold">
                    Oopps ! Terdapat kesalahan dalam pengenalan karakter pada KTP atau SIM B II Umum anda, Silahkan perbaiki data berikut sebelum melanjutkan proses verifikasi :
                </p>
                <ul class="mb-0 ps-3">
                    @if($msg_name_ktp_vs_sim_b2)
                    <li class="mb-1">{{ $msg_name_ktp_vs_sim_b2 }}</li>
                    @endif

                    @if($msg_date_ktp_vs_sim_b2)
                    <li class="mb-1">{{ $msg_date_ktp_vs_sim_b2 }}</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
    @endif

    @if($msg_nik_score)
    <div class="alert border-1 border-primary shadow-sm rounded-3 p-4">
        <div class="d-flex align-items-start">
            <div class="me-3">
                <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
            </div>
            <div>
                <h4 class="mb-2 text-dark fw-bold">Skor kecocokan NIK hasil OCR terlalu rendah!</h4>
                <p class="mb-3 text-danger fw-bold">
                    Silahkan input NIK anda secara manual untuk melanjutkan proses verifikasi.
                </p>
                <form action="{{ route('lowongan-kerja.update', $biodata->id) }}" method="post">
                    @csrf
                    {{ method_field('PUT') }}
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>NO KTP
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="status_ktp" class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Kirim</button>
                </form>
            </div>
        </div>
    </div>
    @endif


    @if($msg_no_ktp || $msg_no_ktp_score)
    <div class="alert border-1 border-primary shadow-sm rounded-3 p-4">
        <div class="d-flex align-items-start">
            <div class="me-3">
                <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
            </div>
            <div>
                <h4 class="mb-2 text-dark fw-bold">Pemeriksaan OCR! (Optical Character Recognition)</h4>
                <p class="mb-3 text-danger fw-bold">
                    Perbaiki dokumen KTP anda sebelum melamar. Pastikan KTP anda sudah sesuai dengan format yang ditentukan :
                </p>
                <ul class="mb-0 ps-3">
                    @if($msg_no_ktp)
                    <li class="mb-1">{{ $msg_no_ktp }}</li>
                    @endif

                    @if($msg_no_ktp_score)
                    <li class="mb-1">{{ $msg_no_ktp_score }}</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
    @endif

    @if (!empty($emptyFields))
    <div class="alert border-1 border-warning shadow-sm rounded-3 p-4">
        <div class="d-flex align-items-start">
            <div class="me-3">
                <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
            </div>
            <div>
                <h4 class="mb-2 text-dark fw-bold">Pemeriksaan Data! Lengkapi Data Sebelum Melamar</h4>
                <p class="mb-3 text-danger fw-bold">
                    Beberapa informasi wajib belum lengkap. Mohon lengkapi data berikut terlebih dahulu:
                </p>
                <ul class="mb-0 ps-3">
                    @foreach ($emptyFields as $field)
                    <li class="mb-1">{{ $field }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <div class="alert border-1 border-success shadow-sm rounded-3 p-4">
        <div class="d-flex align-items-start">
            <div class="me-3">
                <i class="fas fa-check-square fa-2x text-success"></i>
            </div>
            <div>
                <h4 class="mb-2 text-dark fw-bold">Fungsi Reset OCR! (Optical Character Recognition)</h4>
                <p class="mb-3 fw-bold text-success">
                    Lakukan reset OCR jika hasil pengenalan karakter tidak sesuai atau ingin mencoba lagi.
                </p>
                <ul class="mb-0 ps-3">
                    <li class="mb-1">Setelah melakukan pengkinian data</li>
                    <li class="mb-1">Pastikan KTP sudah sesuai dengan format yang ditentukan</li>
                    <li class="mb-1">Klik tombol "Reset OCR" untuk mengulangi proses pengenalan karakter</li>
                    <li class="mb-1">Hasil OCR akan diperbarui sesuai dengan data terbaru</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <a href="{{ route('biodata.index') }}" class="btn btn-primary">Lengkapi Data</a>
        <a href="{{ url()->current() }}?refresh=true"
            class="btn btn-warning float-end"
            id="btn-reset-ocr">
            Reset OCR
        </a>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('btn-reset-ocr').addEventListener('click', function(e) {
        e.preventDefault();
        const url = this.getAttribute('href');

        Swal.fire({
            title: 'Reset OCR?',
            text: "Apakah Anda yakin ingin mereset hasil OCR?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e0a800',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, reset!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });
</script>
@endpush

@endsection