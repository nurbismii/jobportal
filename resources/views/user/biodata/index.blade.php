@extends('layouts.app')

@section('content')

@push('styles')
<style>
    .file-upload-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: #f4f4f4;
        padding: 12px 16px;
        border-radius: 12px;
        font-size: 14px;
    }

    .file-icon {
        color: #009de0;
        margin-right: 10px;
        font-size: 1.5rem;
    }

    .upload-label {
        display: flex;
        align-items: center;
        color: #adb5bd;
    }

    .btn-upload {
        background: linear-gradient(90deg, #bcbcbc, #a0a0a0);
        color: white;
        border: none;
        border-radius: 6px;
        padding: 6px 16px;
        font-weight: 600;
    }

    input[type="file"] {
        display: none;
    }

    .file-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: #f4f4f4;
        padding: 12px 16px;
        border-radius: 12px;
        font-size: 14px;
    }

    .file-info {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #333;
    }

    .file-icon {
        font-size: 1.5rem;
        color: #009de0;
    }

    .file-meta {
        display: flex;
        flex-direction: column;
        color: #6c757d;
    }

    .file-meta .filename {
        color: #495057;
        font-weight: 500;
    }

    .btn-view {
        background: linear-gradient(90deg, #0072b5, #5fc1e8);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 6px 16px;
        font-weight: 600;
    }

    .btn-delete {
        background: linear-gradient(90deg, #83332d, #f2a293);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 6px 16px;
        font-weight: 600;
    }

    .btn-group-custom {
        display: flex;
        gap: 10px;
    }
</style>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

<div class="container-fluid service py-2">
    <div class="container py-5">
        <div class="mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s">
            <h4 class="text-primary">Formulir Biodata</h4>
        </div>
        <form id="formWizard" method="POST" action="{{ route('biodata.store') }}" enctype="multipart/form-data">
            @csrf
            <!-- Step Indicators -->
            <ul class="nav nav-tabs mb-4" id="formTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#step1" type="button">Data Pribadi</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#step2" type="button">Pendidikan</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#step3" type="button">Data Keluarga</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#step4" type="button">Kontak Darurat</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#step5" type="button">Dokumen Pribadi</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#step6" type="button">Pernyataan dan Ajukan</button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Step Biodata -->
                <div class="tab-pane fade show active" id="step1">
                    <h6 class="text-primary">Biodata</h6>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Nama
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama" class="form-control" value="{{ Auth::user()->name }}" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>No KTP
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="no_ktp" class="form-control" value="{{ Auth::user()->no_ktp }}" readonly>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>No Telp
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="no_telp" class="form-control" value="{{ $biodata->no_telp ?? '' }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>No Kartu Keluarga
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="no_kk" class="form-control" maxlength="16" value="{{ $biodata->no_kk ?? '' }}" required>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="jenis_kelamin">Jenis Kelamin
                                <span class="text-danger">*</span>
                            </label>
                            <select name="jenis_kelamin" id="jenis_kelamin" class="form-select" required>
                                @if($biodata)
                                <option value="{{ $biodata->jenis_kelamin }}">{{ $biodata->jenis_kelamin == 'L' ? 'Laki-Laki' : 'Perempuan' }}</option>
                                @else
                                <option value="">Pilih jenis kelamin</option>
                                @endif
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>NPWP <span class="text-danger">*</span></label>
                            <input type="text" id="npwp" name="no_npwp" class="form-control" value="{{ $biodata->no_npwp ?? '' }}" required maxlength="20">
                        </div>
                    </div>

                    <h6 class="text-primary">Alamat KTP</h6>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Tempat Lahir
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="tempat_lahir" class="form-control" value="{{ $biodata->tempat_lahir ?? '' }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Tanggal Lahir
                                <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="tanggal_lahir" class="form-control" value="{{ $biodata->tanggal_lahir ?? '' }}" required>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Provinsi
                                <span class="text-danger">*</span>
                            </label>
                            <select name="provinsi" id="provinsi_id" class="form-select">
                                @if($biodata)
                                <option value="{{ $biodata->provinsi }}">{{ $biodata->getProvinsi->provinsi }}</option>
                                @else
                                <option value="">Pilih provinsi</option>
                                @endif
                                @foreach ($provinsis as $item)
                                <option value="{{ $item->id }}">{{ $item->provinsi }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Kabupaten
                                <span class="text-danger">*</span>
                            </label>
                            <select name="kabupaten" id="kabupaten_id" class="form-select">
                                @if($biodata)
                                <option value="{{ $biodata->kabupaten }}">{{ $biodata->getKabupaten->kabupaten }}</option>
                                @else
                                <option value="">Pilih kabupaten</option>
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Kecamatan
                                <span class="text-danger">*</span>
                            </label>
                            <select name="kecamatan" id="kecamatan_id" class="form-select">
                                @if($biodata)
                                <option value="{{ $biodata->kecamatan }}">{{ $biodata->getKecamatan->kecamatan }}</option>
                                @else
                                <option value="">Pilih kecamatan</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Kelurahan/Desa
                                <span class="text-danger">*</span>
                            </label>
                            <select name="kelurahan" id="kelurahan_id" class="form-select">
                                @if($biodata)
                                <option value="{{ $biodata->kelurahan }}">{{ $biodata->getKelurahan->kelurahan }}</option>
                                @else
                                <option value="">Pilih kelurahan</option>
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="alamat">Alamat Lengkap
                                <span class="text-danger">*</span>
                            </label>
                            <textarea name="alamat" class="form-control" id="alamat">{{ $biodata->alamat ?? '' }}</textarea>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label>Kode Pos
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="kode_pos" class="form-control" maxlength="5" value="{{ $biodata->kode_pos ?? '' }}" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label>RT
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="rt" class="form-control" maxlength="3" value="{{ $biodata->rt ?? '' }}" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label>RW
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="rw" class="form-control" maxlength="3" value="{{ $biodata->rw ?? '' }}" required>
                        </div>
                    </div>

                    <div class="col-md-12 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sesuaiAlamatKtp" checked>
                            <label for="sesuaiAlamatKtp" class="form-check-label">Alamat Domisili sesuai dengan Alamat KTP</label>
                        </div>
                    </div>
                    <h6 class="text-primary">Lain-lain</h6>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Hobi
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="hobi" class="form-control" value="{{ $biodata->hobi ?? '' }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Golongan Darah
                                <span class="text-danger">*</span>
                            </label>
                            <select name="golongan_darah" id="" class="form-select" required>
                                @if($biodata)
                                <option value="{{ $biodata->golongan_darah }}">{{ $biodata->golongan_darah }}</option>
                                @else
                                <option value="">Pilih golongan darah</option>
                                @endif
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="O">O</option>
                                <option value="AB">AB</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Tinggi badan<sup>(cm)</sup>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="tinggi_badan" maxlength="3" value="{{ $biodata->tinggi_badan ?? '' }}" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Berat badan<sup>(kg)</sup>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="berat_badan" maxlength="3" value="{{ $biodata->berat_badan ?? '' }}" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Step Pendidikan -->
                <div class="tab-pane fade" id="step2">
                    <div class="col-md-6 mb-3">
                        <label for="pendidikan_terakhir" class="form-label">Pendidikan Terakhir
                            <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="pendidikan_terakhir" required>
                            @if($biodata)
                            <option value="{{ $biodata->pendidikan_terakhir }}">{{ $biodata->pendidikan_terakhir }}</option>
                            @else
                            <option value="">Pilih pendidikan terakhir</option>
                            @endif
                            <option value="SD">SD</option>
                            <option value="SMP">SMP</option>
                            <option value="SMA">SMA</option>
                            <option value="SMK">SMK</option>
                            <option value="D3">D3</option>
                            <option value="D4">D4</option>
                            <option value="S1">S1</option>
                            <option value="S2">S2</option>
                            <option value="S3">S3</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="nama_instansi" class="form-label">Nama Sekolah/Kampus
                            <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" name="nama_instansi" value="{{ $biodata->nama_instansi ?? '' }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="jurusan" class="form-label">Jurusan
                            <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" name="jurusan" value="{{ $biodata->jurusan ?? '' }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="nilai_ipk" class="form-label">IPK/Nilai Ijazah
                            <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" name="nilai_ipk" value="{{ $biodata->nilai_ipk ?? '' }}" required>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Tahun masuk
                                <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="tahun_masuk" class="form-control" value="{{ $biodata->tahun_masuk ?? '' }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Tahun lulus
                                <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="tahun_lulus" class="form-control" value="{{ $biodata->tahun_lulus ?? '' }}">
                        </div>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label>Prestasi</label>
                        <textarea type="text" name="prestasi" rows="5" class="form-control">{{ $biodata->prestasi ?? '' }}</textarea>
                    </div>
                </div>

                <!-- Step Keluarga -->
                <div class="tab-pane fade" id="step3">
                    <h6 class="text-primary">Nama orang tua</h6>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Nama ibu
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama_ibu" class="form-control" value="{{ $biodata->nama_ibu ?? '' }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Nama ayah
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama_ayah" class="form-control" value="{{ $biodata->nama_ayah ?? '' }}" required>
                        </div>
                    </div>
                    <h6 class="text-primary">Status pernikahan</h6>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="status_pernikahan">Status pernikahan
                                <span class="text-danger">*</span>
                            </label>
                            <select name="status_pernikahan" class="form-select" id="status_pernikahan" required>
                                @if($biodata)
                                <option value="{{ $biodata->status_pernikahan }}">{{ $biodata->status_pernikahan }}</option>
                                @else
                                <option value="">Pilih status pernikahan</option>
                                @endif
                                <option value="Belum Kawin">Belum Kawin</option>
                                <option value="Kawin">Kawin</option>
                                <option value="Cerai Hidup">Cerai Hidup</option>
                                <option value="Cerai Mati">Cerai Mati</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tanggal_nikah">Tanggal pernikahan</label>
                            <input type="date" name="tanggal_nikah" id="tanggal_nikah" value="{{ $biodata->tanggal_nikah ?? '' }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Nama Suami/Istri</label>
                        <input type="text" name="nama_pasangan" class="form-control" value="{{ $biodata->nama_pasangan ?? '' }}">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Jumlah anak</label>
                            <input type="number" name="jumlah_anak" maxlength="1" max="3" class="form-control" value="{{ $biodata->jumlah_anak ?? '' }}">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Nama anak ke-1</label>
                            <input type="text" name="nama_anak_1" class="form-control" id="" value="{{ $biodata->nama_anak_1 ?? '' }}">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Nama anak ke-2</label>
                            <input type="text" name="nama_anak_2" class="form-control" id="" value="{{ $biodata->nama_anak_2 ?? '' }}">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Nama anak ke-3</label>
                            <input type="text" name="nama_anak_3" class="form-control" id="" value="{{ $biodata->nama_anak_3 ?? '' }}">
                        </div>
                    </div>
                </div>

                <!-- Step Kontak Darurat -->
                <div class="tab-pane fade" id="step4">
                    <div class="col-md-6 mb-3">
                        <label>Nama kontak darurat
                            <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="nama_kontak_darurat" class="form-control" value="{{ $biodata->nama_kontak_darurat ?? '' }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>No telepon
                            <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="no_telp_darurat" class="form-control" value="{{ $biodata->no_telepon_darurat ?? '' }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Status hubungan
                            <span class="text-danger">*</span>
                        </label>
                        <select name="status_hubungan" id="status_hubungan" class="form-select" required>
                            @if($biodata)
                            <option value="{{ $biodata->status_hubungan }}">{{ $biodata->status_hubungan }}</option>
                            @else
                            <option value="">Pilih status hubungan</option>
                            @endif
                            <option value="Orang Tua">Orang Tua</option>
                            <option value="Saudara">Saudara</option>
                            <option value="Sepupu">Sepupu</option>
                            <option value="Teman Dekat">Teman Dekat</option>
                        </select>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="tab-pane fade" id="step5">

                    <div class="accordion" id="alertAccordion">
                        <div class="accordion-item rounded-3 shadow-sm mb-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button bg-opacity-25 text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#alertBody" aria-expanded="true" aria-controls="alertBody">
                                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                    Meningkatkan Hasil
                                </button>
                            </h2>
                            <div id="alertBody" class="accordion-collapse collapse show" data-bs-parent="#alertAccordion">
                                <div class="row g-5 align-items-center">
                                    <div class="col-xl-6 wow fadeInLeft" data-wow-delay="0.2s" style="visibility: visible; animation-delay: 0.2s; animation-name: fadeInLeft;">
                                        <div class="accordion-body">
                                            <p class="mb-3 fw-bold">
                                                Untuk menghasilkan hasil yang optimal berikut beberapa tips yang bisa di ikuti :
                                            </p>
                                            <ul class="mb-0 ps-3">
                                                <li class="mb-1">Direkomendasikan posisi KTP dan SIM gambar tegak.</li>
                                                <li class="mb-1">Hasil foto harus jelas, tidak blur, tidak pecah, dan dapat dibaca</li>
                                                <li class="mb-1">Gambar diambil dengan pencahayaan yang bagus dan tidak terlalu jauh</li>
                                                <li class="mb-1">Tidak mengandung tulisan lain selain dari dokumen.</li>
                                            </ul>
                                            <span>
                                                <small class="text-danger mt-2 d-block">
                                                    Apabila unggahan KTP dan SIM tidak sesuai dengan ketentuan di atas, proses lamaran pekerjaan kamu dapat mengalami kendala.
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

                    <div class="row g-3 wow fadeInDown" data-wow-delay="0.2s" style="visibility: visible; animation-delay: 0.4s; animation-name: fadeInDown;">

                        @if($biodata && !$biodata->cv)
                        <div class="col-md-6 mb-2">
                            <label class="form-label">CV (pdf)</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <span id="file-name-cv">Dokumen belum diunggah</span>
                                </div>
                                <label for="cv-upload" class="btn btn-upload">Unggah</label>
                                <input type="file" name="cv" id="cv-upload" accept=".pdf">
                            </div>
                        </div>
                        @else
                        <div class="col-md-6 mb-2">
                            <label class="form-label">CV</label>
                            <div class="file-box">
                                <div class="file-info">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <div class="file-meta">
                                        <span class="file-name-cv">{{ $biodata->cv }}</span>
                                        <input type="hidden" name="cv" value="{{ $biodata->cv }}">
                                    </div>
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset(Auth::user()->no_ktp . '/dokumen/' . $biodata->cv) }}" target="_blank" class="btn btn-view">Lihat</a>
                                    <input type="file" name="cv" id="cv-upload" value="{{ $biodata->cv }}">
                                    <button type="button"
                                        class="btn btn-delete btn-confirm-delete"
                                        data-url="{{ route('biodata.deleteFile', ['field' => 'cv']) }}"
                                        data-field="cv">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($biodata && !$biodata->pas_foto)
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Pas Foto 3x4 (jpeg, jpg, png)</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <span id="file-name-pas-foto">Dokumen belum diunggah</span>
                                </div>
                                <label for="pas-foto-upload" class="btn btn-upload">Unggah</label>
                                <input type="file" name="pas_foto" id="pas-foto-upload" accept=".png,.jpg,.jpeg">
                            </div>
                        </div>
                        @else
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Pas Foto 3x4</label>
                            <div class="file-box">
                                <div class="file-info">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <div class="file-meta">
                                        <span class="file-name-pas-foto">{{ $biodata->pas_foto }}</span>
                                        <input type="hidden" name="pas_foto" value="{{ $biodata->pas_foto }}">
                                    </div>
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset(Auth::user()->no_ktp . '/dokumen/' . $biodata->pas_foto) }}" target="_blank" class="btn btn-view">Lihat</a>
                                    <button type="button"
                                        class="btn btn-delete btn-confirm-delete"
                                        data-url="{{ route('biodata.deleteFile', ['field' => 'pas_foto']) }}"
                                        data-field="pas_foto">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($biodata && !$biodata->surat_lamaran)
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Surat Lamaran Kerja (pdf)</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <span id="file-name-slk">Dokumen belum diunggah</span>
                                </div>
                                <label for="slk-upload" class="btn btn-upload">Unggah</label>
                                <input type="file" id="slk-upload" name="surat_lamaran" accept=".pdf">
                            </div>
                        </div>
                        @else
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Surat Lamaran Kerja</label>
                            <div class="file-box">
                                <div class="file-info">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <div class="file-meta">
                                        <span class="file-name-slk">{{ $biodata->surat_lamaran }}</span>
                                        <input type="hidden" name="surat_lamaran" value="{{ $biodata->surat_lamaran }}">
                                    </div>
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset(Auth::user()->no_ktp . '/dokumen/' . $biodata->surat_lamaran) }}" target="_blank" class="btn btn-view">Lihat</a>
                                    <button type="button"
                                        class="btn btn-delete btn-confirm-delete"
                                        data-url="{{ route('biodata.deleteFile', ['field' => 'surat_lamaran']) }}"
                                        data-field="surat_lamaran">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($biodata && !$biodata->ijazah)
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Ijazah dan Transkrip nilai (pdf)</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <span id="file-name-ijazah">Dokumen belum diunggah</span>
                                </div>
                                <label for="ijazah-upload" class="btn btn-upload">Unggah</label>
                                <input type="file" id="ijazah-upload" name="ijazah" accept=".pdf">
                            </div>
                        </div>
                        @else
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Ijazah dan Transkrip nilai</label>
                            <div class="file-box">
                                <div class="file-info">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <div class="file-meta">
                                        <span class="file-name-ijazah">{{ $biodata->ijazah }}</span>
                                        <input type="hidden" name="ijazah" value="{{ $biodata->ijazah }}">
                                    </div>
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset(Auth::user()->no_ktp . '/dokumen/' . $biodata->ijazah) }}" target="_blank" class="btn btn-view">Lihat</a>
                                    <button type="button"
                                        class="btn btn-delete btn-confirm-delete"
                                        data-url="{{ route('biodata.deleteFile', ['field' => 'ijazah']) }}"
                                        data-field="ijazah">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($biodata && !$biodata->ktp)
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Kartu Tanda Penduduk (KTP) (jpg, jpeg, png)</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <span id="file-name-ktp">Dokumen belum diunggah</span>
                                </div>
                                <label for="ktp-upload" class="btn btn-upload">Unggah</label>
                                <input type="file" id="ktp-upload" name="ktp" accept=".jpg,.jpeg,.png">
                            </div>
                        </div>
                        @else
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Kartu Tanda Penduduk (KTP)</label>
                            <div class="file-box">
                                <div class="file-info">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <div class="file-meta">
                                        <span class="file-name-ktp">{{ $biodata->ktp }}</span>
                                        <input type="hidden" name="ktp" value="{{ $biodata->ktp }}">
                                    </div>
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset(Auth::user()->no_ktp . '/dokumen/' . $biodata->ktp) }}" target="_blank" class="btn btn-view">Lihat</a>
                                    <button type="button"
                                        class="btn btn-delete btn-confirm-delete"
                                        data-url="{{ route('biodata.deleteFile', ['field' => 'ktp']) }}"
                                        data-field="ktp">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($biodata && !$biodata->sim_b_2)
                        <div class="col-md-6 mb-2">
                            <label class="form-label">SIM B II Umum/SIO (jpg, jpeg, png)<sup>Opsional</sup></label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <span id="file-name-sim">Dokumen belum diunggah</span>
                                </div>
                                <label for="sim-upload" class="btn btn-upload">Unggah</label>
                                <input type="file" id="sim-upload" name="sim_b_2" accept=".jpg,.jpeg,.png">
                            </div>
                        </div>
                        @else
                        <div class="col-md-6 mb-2">
                            <label class="form-label">SIM B II Umum/SIO <sup>Opsional</sup></label>
                            <div class="file-box">
                                <div class="file-info">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <div class="file-meta">
                                        <span class="file-name-sim">{{ $biodata->sim_b_2 }}</span>
                                        <input type="hidden" name="sim_b_2" value="{{ $biodata->sim_b_2 }}">
                                    </div>
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset(Auth::user()->no_ktp . '/dokumen/' . $biodata->sim_b_2) }}" target="_blank" class="btn btn-view">Lihat</a>
                                    <button type="button"
                                        class="btn btn-delete btn-confirm-delete"
                                        data-url="{{ route('biodata.deleteFile', ['field' => 'sim_b_2']) }}"
                                        data-field="sim_b_2">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($biodata && !$biodata->skck)
                        <div class="col-md-6 mb-2">
                            <label class="form-label">SKCK (pdf)</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <span id="file-name-skck">Dokumen belum diunggah</span>
                                </div>
                                <label for="skck-upload" class="btn btn-upload">Unggah</label>
                                <input type="file" name="skck" id="skck-upload" accept=".pdf">
                            </div>
                        </div>
                        @else
                        <div class="col-md-6 mb-2">
                            <label class="form-label">SKCK</label>
                            <div class="file-box">
                                <div class="file-info">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <div class="file-meta">
                                        <span class="file-name-skck">{{ $biodata->skck }}</span>
                                        <input type="hidden" name="skck" value="{{ $biodata->skck }}">
                                    </div>
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset(Auth::user()->no_ktp . '/dokumen/' . $biodata->skck) }}" target="_blank" class="btn btn-view">Lihat</a>
                                    <button type="button"
                                        class="btn btn-delete btn-confirm-delete"
                                        data-url="{{ route('biodata.deleteFile', ['field' => 'skck']) }}"
                                        data-field="skck">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($biodata && !$biodata->sertifikat_vaksin)
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Sertifikat Vaksin (pdf)</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <span id="file-name-sertifikat">Dokumen belum diunggah</span>
                                </div>
                                <label for="sertifikat-upload" class="btn btn-upload">Unggah</label>
                                <input type="file" name="sertifikat_vaksin" id="sertifikat-upload" accept=".pdf">
                            </div>
                        </div>
                        @else
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Sertifikat Vaksin</label>
                            <div class="file-box">
                                <div class="file-info">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <div class="file-meta">
                                        <span class="file-name-sertifikat">{{ $biodata->sertifikat_vaksin }}</span>
                                        <input type="hidden" name="sertifikat_vaksin" value="{{ $biodata->sertifikat_vaksin }}">
                                    </div>
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset(Auth::user()->no_ktp . '/dokumen/' . $biodata->sertifikat_vaksin) }}" target="_blank" class="btn btn-view">Lihat</a>
                                    <button type="button"
                                        class="btn btn-delete btn-confirm-delete"
                                        data-url="{{ route('biodata.deleteFile', ['field' => 'sertifikat_vaksin']) }}"
                                        data-field="sertifikat_vaksin">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($biodata && !$biodata->kartu_keluarga)
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Kartu Keluarga (pdf)</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <span id="file-name-kk">Dokumen belum diunggah</span>
                                </div>
                                <label for="kk-upload" class="btn btn-upload">Unggah</label>
                                <input type="file" name="kartu_keluarga" id="kk-upload" accept=".pdf">
                            </div>
                        </div>
                        @else
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Kartu Keluarga</label>
                            <div class="file-box">
                                <div class="file-info">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <div class="file-meta">
                                        <span class="file-name-kk">{{ $biodata->kartu_keluarga }}</span>
                                        <input type="hidden" name="kartu_keluarga" value="{{ $biodata->kartu_keluarga }}">
                                    </div>
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset(Auth::user()->no_ktp . '/dokumen/' . $biodata->kartu_keluarga) }}" target="_blank" class="btn btn-view">Lihat</a>
                                    <button type="button"
                                        class="btn btn-delete btn-confirm-delete"
                                        data-url="{{ route('biodata.deleteFile', ['field' => 'kartu_keluarga']) }}"
                                        data-field="kartu_keluarga">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($biodata && !$biodata->npwp)
                        <div class="col-md-6 mb-2">
                            <label class="form-label">NPWP (pdf)</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <span id="file-name-npwp">Dokumen belum diunggah</span>
                                </div>
                                <label for="npwp-upload" class="btn btn-upload">Unggah</label>
                                <input type="file" name="npwp" id="npwp-upload" accept=".pdf">
                            </div>
                        </div>
                        @else
                        <div class="col-md-6 mb-2">
                            <label class="form-label">NPWP</label>
                            <div class="file-box">
                                <div class="file-info">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <div class="file-meta">
                                        <span class="file-name-npwp">{{ $biodata->npwp }}</span>
                                        <input type="hidden" name="npwp" value="{{ $biodata->npwp }}">
                                    </div>
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset(Auth::user()->no_ktp . '/dokumen/' . $biodata->npwp) }}" target="_blank" class="btn btn-view">Lihat</a>
                                    <button type="button"
                                        class="btn btn-delete btn-confirm-delete"
                                        data-url="{{ route('biodata.deleteFile', ['field' => 'npwp']) }}"
                                        data-field="npwp">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($biodata && !$biodata->ak1)
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Kartu Pencari Kejra (AK1) (pdf)</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <span id="file-name-ak1">Dokumen belum diunggah</span>
                                </div>
                                <label for="ak1-upload" class="btn btn-upload">Unggah</label>
                                <input type="file" name="ak1" id="ak1-upload" accept=".pdf">
                            </div>
                        </div>
                        @else
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Kartu Pencari Kejra (AK1)</label>
                            <div class="file-box">
                                <div class="file-info">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <div class="file-meta">
                                        <span class="file-name-ak1">{{ $biodata->ak1 }}</span>
                                        <input type="hidden" name="ak1" value="{{ $biodata->ak1 }}">
                                    </div>
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset(Auth::user()->no_ktp . '/dokumen/' . $biodata->ak1) }}" target="_blank" class="btn btn-view">Lihat</a>
                                    <button type="button"
                                        class="btn btn-delete btn-confirm-delete"
                                        data-url="{{ route('biodata.deleteFile', ['field' => 'ak1']) }}"
                                        data-field="ak1">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($biodata && !$biodata->sertifikat_pendukung)
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Sertifikat Pendukung (pdf)</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <span id="file-name-serti-pendukung">Dokumen belum diunggah</span>
                                </div>
                                <label for="serti-pendukung-upload" class="btn btn-upload">Unggah</label>
                                <input type="file" name="sertifikat_pendukung" id="serti-pendukung-upload" accept=".pdf">
                            </div>
                        </div>
                        @else
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Sertifikat Pendukung</label>
                            <div class="file-box">
                                <div class="file-info">
                                    <i class="bi bi-file-earmark-text file-icon"></i>
                                    <div class="file-meta">
                                        <span class="file-name-serti-pendukung">{{ $biodata->sertifikat_pendukung }}</span>
                                        <input type="hidden" name="sertifikat_pendukung" value="{{ $biodata->sertifikat_pendukung }}">
                                    </div>
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset(Auth::user()->no_ktp . '/dokumen/' . $biodata->sertifikat_pendukung) }}" target="_blank" class="btn btn-view">Lihat</a>
                                    <button type="button"
                                        class="btn btn-delete btn-confirm-delete"
                                        data-url="{{ route('biodata.deleteFile', ['field' => 'sertifikat_pendukung']) }}"
                                        data-field="sertifikat_pendukung">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Step 6 -->
                <div class="tab-pane fade" id="step6">
                    <h6 class="text-primary">Pernyataan Keaslian Data</h6>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="checkBox1" name="pernyataan_1" value="Dengan ini, Saya ( {{ Auth::user()->name}} ) menyatakan bahwa seluruh data dan dokumen yang saya input dan unggah adalah benar dan asli.">
                                <label class="form-check-label" for="checkBox1">Dengan ini, Saya ( {{ Auth::user()->name}} ) menyatakan bahwa seluruh data dan dokumen yang saya input dan unggah adalah benar dan asli.</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="checkBox2" name="pernyataan_2" value="Saya memahami bahwa apabila terbukti melakukan pemalsuan data, saya bersedia menerima konsekuensinya, termasuk tidak diluluskan dalam proses rekrutmen.">
                                <label class="form-check-label" for="checkBox2">Saya memahami bahwa apabila terbukti melakukan pemalsuan data, saya bersedia menerima konsekuensinya, termasuk tidak diluluskan dalam proses rekrutmen.</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="mt-4 d-flex justify-content-between">
                <!-- Tombol Sebelumnya di kiri -->
                <button type="button" class="btn btn-dark" id="prevBtn" disabled>Sebelumnya</button>

                <!-- Tombol Selanjutnya dan Submit di kanan -->
                <div class="d-flex gap-2 ms-auto">
                    <button type="submit" class="btn btn-success" id="submitBtn">Ajukan</button>
                    <button type="button" class="btn btn-primary" id="nextBtn">Selanjutnya</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    function toggleAlertContent(button) {
        const content = document.getElementById('alertContent');
        const icon = button.querySelector('i');

        if (content.style.display === 'none') {
            content.style.display = 'flex';
            icon.classList.remove('fa-plus');
            icon.classList.add('fa-minus');
        } else {
            content.style.display = 'none';
            icon.classList.remove('fa-minus');
            icon.classList.add('fa-plus');
        }
    }
</script>

<script>
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('#provinsi_id').on('change', function() {
            var provinsiID = $(this).val();
            if (provinsiID) {
                $.ajax({
                    url: 'api/kabupaten/' + provinsiID,
                    type: "GET",
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data) {
                            $('#kabupaten_id').empty();
                            $('#kabupaten_id').append('<option hidden>Pilih kabupaten</option>');
                            $.each(data, function(id, kabupaten) {
                                $('select[name="kabupaten"]').append('<option value="' + kabupaten.id + '">' + kabupaten.kabupaten + '</option>');
                            });
                        } else {
                            $('#kabupaten_id').empty();
                        }
                    }
                });
            } else {
                $('#kabupaten_id').empty();
            }
        });

        $('#kabupaten_id').on('change', function() {
            var kabupatenID = $(this).val();
            if (kabupatenID) {
                $.ajax({
                    url: 'api/kecamatan/' + kabupatenID,
                    type: "GET",
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data) {
                            $('#kecamatan_id').empty();
                            $('#kecamatan_id').append('<option hidden>Pilih kecamatan</option>');
                            $.each(data, function(id, kecamatan) {
                                $('select[name="kecamatan"]').append('<option value="' + kecamatan.id + '">' + kecamatan.kecamatan + '</option>');
                            })
                        } else {
                            $('#kecamatan_id').empty();
                        }
                    }
                });
            }
        });

        $('#kecamatan_id').on('change', function() {
            var kecamatanID = $(this).val();
            if (kecamatanID) {
                $.ajax({
                    url: 'api/kelurahan/' + kecamatanID,
                    type: "GET",
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data) {
                            $('#kelurahan_id').empty();
                            $('#kelurahan_id').append('<option hidden>Pilih kelurahan/desa</option>');
                            console.log(data);
                            $.each(data, function(id, kelurahan) {
                                $('select[name="kelurahan"]').append('<option value="' + kelurahan.id + '">' + kelurahan.kelurahan + '</option>');
                            })
                        } else {
                            $('#kelurahan_id').empty();
                        }
                    }
                });
            }
        });
    });
</script>

<script>
    const tabs = Array.from(document.querySelectorAll('#formTabs .nav-link'));

    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    let currentStep = 0;

    document.addEventListener('DOMContentLoaded', () => {
        showStep(currentStep);

        // Mencegah klik langsung pada tab selain currentStep
        tabs.forEach((tab, index) => {
            tab.addEventListener('show.bs.tab', function(e) {
                if (index > currentStep) {
                    if (!validateStep(currentStep)) {
                        e.preventDefault();
                        return;
                    }
                }
                currentStep = index;
                updateNavButtons();
            });
        });
    });

    function showStep(index) {
        tabs[index].click(); // Trigger tab change
        updateNavButtons(); // Perbarui tombol
    }

    function validateStep(index) {
        const stepPane = document.querySelectorAll('.tab-pane')[index];
        const inputs = stepPane.querySelectorAll('input, select');
        for (const input of inputs) {
            if (!input.checkValidity()) {
                input.reportValidity();
                return false;
            }
        }
        return true;
    }

    nextBtn.addEventListener('click', () => {
        if (validateStep(currentStep)) {
            currentStep++;
            showStep(currentStep);
        }
    });

    prevBtn.addEventListener('click', () => {
        currentStep--;
        showStep(currentStep);
    });

    function updateNavButtons() {
        prevBtn.disabled = currentStep === 0;
        nextBtn.classList.toggle('d-none', currentStep === tabs.length - 1);
        submitBtn.classList.toggle('d-none', currentStep !== tabs.length - 1);
    }

    document.addEventListener('DOMContentLoaded', () => showStep(currentStep));

    function checkCheckboxes() {
        const checkBox1 = document.getElementById('checkBox1');
        const checkBox2 = document.getElementById('checkBox2');
        const submitBtn = document.getElementById('submitBtn');

        if (checkBox1.checked && checkBox2.checked) {
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Cek awal
        checkCheckboxes();

        // Cek ulang setiap kali checkbox berubah
        document.getElementById('checkBox1').addEventListener('change', checkCheckboxes);
        document.getElementById('checkBox2').addEventListener('change', checkCheckboxes);
    });

    const cvUpload = document.getElementById('cv-upload');
    if (cvUpload) {
        cvUpload.addEventListener('change', function() {
            const fileName = cvUpload.files[0]?.name || 'Dokumen belum diunggah';
            document.getElementById('file-name-cv').textContent = fileName;
        });
    }

    const pasFotoUpload = document.getElementById('pas-foto-upload');
    if (pasFotoUpload) {
        pasFotoUpload.addEventListener('change', function() {
            const fileName = pasFotoUpload.files[0]?.name || 'Dokumen belum diunggah';
            document.getElementById('file-name-pas-foto').textContent = fileName;
        });
    }

    const slkUpload = document.getElementById('slk-upload');
    if (slkUpload) {
        slkUpload.addEventListener('change', function() {
            const fileName = slkUpload.files[0]?.name || 'Dokumen belum diunggah';
            document.getElementById('file-name-slk').textContent = fileName;
        });
    }

    const ijazahUpload = document.getElementById('ijazah-upload');
    if (ijazahUpload) {
        ijazahUpload.addEventListener('change', function() {
            const fileName = ijazahUpload.files[0]?.name || 'Dokumen belum diunggah';
            document.getElementById('file-name-ijazah').textContent = fileName;
        });
    }

    const ktpUpload = document.getElementById('ktp-upload');
    if (ktpUpload) {
        ktpUpload.addEventListener('change', function() {
            const fileName = ktpUpload.files[0]?.name || 'Dokumen belum diunggah';
            document.getElementById('file-name-ktp').textContent = fileName;
        });
    }

    const simUpload = document.getElementById('sim-upload');
    if (simUpload) {
        simUpload.addEventListener('change', function() {
            const fileName = simUpload.files[0]?.name || 'Dokumen belum diunggah';
            document.getElementById('file-name-sim').textContent = fileName;
        });
    }

    const skckUpload = document.getElementById('skck-upload');
    if (skckUpload) {
        skckUpload.addEventListener('change', function() {
            const fileName = skckUpload.files[0]?.name || 'Dokumen belum diunggah';
            document.getElementById('file-name-skck').textContent = fileName;
        });
    }

    const sertifikatUpload = document.getElementById('sertifikat-upload');
    if (sertifikatUpload) {
        sertifikatUpload.addEventListener('change', function() {
            const fileName = sertifikatUpload.files[0]?.name || 'Dokumen belum diunggah';
            document.getElementById('file-name-sertifikat').textContent = fileName;
        });
    }

    const kkUpload = document.getElementById('kk-upload');
    if (kkUpload) {
        kkUpload.addEventListener('change', function() {
            const fileName = kkUpload.files[0]?.name || 'Dokumen belum diunggah';
            document.getElementById('file-name-kk').textContent = fileName;
        });
    }

    const npwpUpload = document.getElementById('npwp-upload');
    if (npwpUpload) {
        npwpUpload.addEventListener('change', function() {
            const fileName = npwpUpload.files[0]?.name || 'Dokumen belum diunggah';
            document.getElementById('file-name-npwp').textContent = fileName;
        });
    }

    const ak1Upload = document.getElementById('ak1-upload');
    if (ak1Upload) {
        ak1Upload.addEventListener('change', function() {
            const fileName = ak1Upload.files[0]?.name || 'Dokumen belum diunggah';
            document.getElementById('file-name-ak1').textContent = fileName;
        });
    }

    const sertiPendukungUpload = document.getElementById('serti-pendukung-upload');
    if (sertiPendukungUpload) {
        sertiPendukungUpload.addEventListener('change', function() {
            const fileName = sertiPendukungUpload.files[0]?.name || 'Dokumen belum diunggah';
            document.getElementById('file-name-serti-pendukung').textContent = fileName;
        });
    }

    document.getElementById('npwp').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, ''); // Hanya angka
        let formatted = '';

        if (value.length > 0) formatted += value.substr(0, 2);
        if (value.length >= 3) formatted += '.' + value.substr(2, 3);
        if (value.length >= 6) formatted += '.' + value.substr(5, 3);
        if (value.length >= 9) formatted += '.' + value.substr(8, 1);
        if (value.length >= 10) formatted += '-' + value.substr(9, 3);
        if (value.length >= 13) formatted += '.' + value.substr(12, 3);

        e.target.value = formatted;
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-confirm-delete').forEach(function(button) {
            button.addEventListener('click', function() {
                const url = this.getAttribute('data-url');
                const field = this.getAttribute('data-field');

                if (confirm(`Yakin ingin menghapus ${field}?`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = url;

                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = '{{ csrf_token() }}';

                    const method = document.createElement('input');
                    method.type = 'hidden';
                    method.name = '_method';
                    method.value = 'DELETE';

                    form.appendChild(csrf);
                    form.appendChild(method);

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
</script>

@endpush

@endsection