@extends('layouts.app')

@section('content')
<div class="container py-5">
    @if($msg_name_ktp_vs_sim_b2 || $msg_date_ktp_vs_sim_b2)
    <div class="accordion" id="alertAccordion">
        <div class="accordion-item rounded-3 shadow-sm mb-3">
            <h2 class="accordion-header">
                <button class="accordion-button bg-opacity-25 text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#alertBody" aria-expanded="true" aria-controls="alertBody">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Petunjuk Pengunggahan Dokumen
                </button>
            </h2>
            <div id="alertBody" class="accordion-collapse collapse show" data-bs-parent="#alertAccordion">
                <div class="row g-5 align-items-center">
                    <div class="col-xl-6 wow fadeInLeft" data-wow-delay="0.2s" style="visibility: visible; animation-delay: 0.2s; animation-name: fadeInLeft;">
                        <div class="accordion-body">
                            <p class="mb-3 fw-bold">
                                Agar dokumen lamaran kamu bisa diproses dengan lancar, pastikan:
                            </p>
                            <ul class="mb-0 ps-3">
                                <li class="mb-1">Foto dokumen diambil dalam posisi tegak (tidak miring atau terbalik)</li>
                                <li class="mb-1">Teks pada dokumen terlihat jelas dan mudah dibaca</li>
                                <li class="mb-1">Gunakan pencahayaan yang cukup, jangan terlalu gelap atau silau</li>
                                <li class="mb-1">Pastikan tidak ada bagian penting yang tertutup, misalnya oleh tangan, stiker, atau pantulan cahaya</li>
                                <li class="mb-1">Jangan menambahkan tulisan, coretan, atau gambar lain di luar isi dokumen</li>
                            </ul>
                            <span>
                                <small class="text-danger mt-2 d-block">
                                    Jika dokumen tidak sesuai, proses pengajuanmu bisa terhambat atau tidak diterima. Harap pastikan semua sudah benar sebelum mengunggah!
                                </small>
                            </span>
                        </div>
                    </div>
                    <div class="col-xl-3 wow fadeInRight" data-wow-delay="0.4s" style="visibility: visible; animation-delay: 0.4s; animation-name: fadeInRight;">
                        <div class="rounded p-3 d-inline-block text-center">
                            <p class="mb-0 fw-bold">
                                Contoh letak foto KTP yang baik
                            </p>
                            <img src="{{ asset('img/example-ktp.jpg') }}" alt="foto contoh KTP" class="img-fluid w-100" style="height: 160px;">
                        </div>
                    </div>
                    <div class="col-xl-3 wow fadeInRight" data-wow-delay="0.4s" style="visibility: visible; animation-delay: 0.4s; animation-name: fadeInRight;">
                        <div class="rounded p-3 d-inline-block text-center">
                            <p class="mb-2 fw-bold">
                                Contoh letak foto SIM yang baik
                            </p>
                            <img src="{{ asset('img/example-sim_b2.png') }}" alt="foto contoh KTP" class="img-fluid w-100" style="height: 160px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($msg_name_ktp_vs_sim_b2 || $msg_date_ktp_vs_sim_b2)
    <div class="alert border-1 border-danger shadow-sm rounded-3 p-4">
        <div class="d-flex align-items-start">
            <div class="me-3">
                <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
            </div>
            <div>
                <h4 class="mb-2 text-dark fw-bold">Pemeriksaan Dokumen KTP dan SIM B II Umum!</h4>
                <p class="mb-3 text-danger fw-bold">
                    Oopss! Data tidak sesuai, Silahkan perbaiki data berikut sebelum melanjutkan proses verifikasi :
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
                <h4 class="mb-2 text-dark fw-bold">Verifikasi kepemilikan KTP!</h4>
                <p class="mb-3 text-danger fw-bold">
                    Silahkan input NIK anda untuk melanjutkan proses verifikasi.
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
                <h4 class="mb-2 text-dark fw-bold">Pemeriksaan KTP!</h4>
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
                <h4 class="mb-2 text-dark fw-bold">Pemeriksaan Data! Lengkapi dokumen pribadi sebelum melamar</h4>
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
                <h4 class="mb-2 text-dark fw-bold">Fungsi Pengkinian Data!</h4>
                <p class="mb-3 fw-bold text-success">
                    Lakukan pengkinian data jika hasil pengenalan karakter tidak sesuai atau ingin mencoba lagi.
                </p>
                <ul class="mb-0 ps-3">
                    <li class="mb-1">Setelah melakukan pengkinian data</li>
                    <li class="mb-1">Pastikan KTP sudah sesuai dengan format yang ditentukan</li>
                    <li class="mb-1">Klik link "Pengkinian Data" untuk mengulangi proses pemeriksaan</li>
                    <li class="mb-1">Hasil akan diperbarui sesuai dengan data terbaru</li>
                    <li class="mb-1">Lakukan <a href="{{ url()->current() }}?refresh=true" id="btn-reset-ocr">Pengkinian Data! </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <a href="{{ route('biodata.index') }}" class="btn btn-primary">Lengkapi Data</a>
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