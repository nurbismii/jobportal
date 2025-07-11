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

    .sticky-tabs {
        position: sticky;
        top: 96px;
        /* Atur sesuai tinggi navbar/header Anda */
        z-index: 1030;
        /* Pastikan di atas konten lain */
        background: #fff;
        /* Agar tidak transparan */
        border-radius: 0.5rem 0.5rem 0 0;
    }
</style>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

<div class="container-fluid service py-2">
    <div class="container py-5">
        <div class="mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s">
            <h4 class="text-primary" id="start">Formulir Biodata</h4>
        </div>
        <form id="formWizard" method="POST" action="{{ route('biodata.store') }}" enctype="multipart/form-data">
            @csrf
            <!-- Step Indicators -->
            <ul class="nav nav-tabs mb-4 sticky-tabs bg-white z-3" id="formTabs" role="tablist">
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
                            <input type="tel"
                                name="no_telp"
                                class="form-control"
                                value="{{ $biodata->no_telp ?? '' }}"
                                pattern="^(?:\+62|62|0)[2-9][0-9]{7,11}$"
                                title="Masukkan nomor telepon Indonesia yang valid (misalnya 08123456789 atau +628123456789)"
                                required>
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
                                @php
                                $genderOptions = [
                                'M 男' => 'Laki-laki 男',
                                'F 女' => 'Perempuan 女'
                                ];
                                $selectedGender = $biodata->jenis_kelamin ?? '';
                                @endphp
                                <option value="">Pilih jenis kelamin</option>
                                @foreach($genderOptions as $value => $label)
                                <option value="{{ $value }}" {{ $selectedGender === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>NPWP <span class="text-danger">*</span></label>
                            <input type="text" id="npwp" name="no_npwp" class="form-control" value="{{ $biodata->no_npwp ?? '' }}" required maxlength="20">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="agama">Agama
                                <span class="text-danger">*</span>
                            </label>
                            <select name="agama" id="agama" class="form-select" required>
                                @php
                                $agamaOptions = [
                                'ISLAM 伊斯兰教',
                                'KRISTEN PROTESTAN 基督教新教',
                                'KRISTEN KATHOLIK 天主教徒',
                                'BUDHA 佛教',
                                'HINDU 印度教',
                                'KHONGHUCU 儒教',
                                ];
                                $selectedAgama = $biodata->agama ?? '';
                                @endphp
                                <option value="">Pilih agama</option>
                                @foreach($agamaOptions as $agama)
                                <option value="{{ $agama }}" {{ $selectedAgama === $agama ? 'selected' : '' }}>{{ $agama }}</option>
                                @endforeach
                            </select>
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
                            <input class="form-check-input" type="checkbox" id="sesuaiAlamatKtp">
                            <label for="sesuaiAlamatKtp" class="form-check-label">Alamat KTP sesuai dengan domisili</label>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3" id="alamatDomisiliField" style="display: none;">
                        <label for="alamat">Alamat Domisili <span class="text-danger">*</span></label>
                        <textarea name="alamat_domisili" class="form-control" id="alamat_domisili">{{ $biodata->alamat_domisili ?? '' }}</textarea>
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
                                @php
                                $golonganOptions = ['A 型', 'B 型', 'O 型', 'AB 型'];
                                $selectedGolongan = $biodata->golongan_darah ?? '';
                                @endphp
                                <option value="">Pilih golongan darah</option>
                                @if($selectedGolongan && in_array($selectedGolongan, $golonganOptions))
                                <option value="{{ $selectedGolongan }}" selected>{{ $selectedGolongan }}</option>
                                @endif
                                @foreach($golonganOptions as $golongan)
                                @if($golongan !== $selectedGolongan)
                                <option value="{{ $golongan }}">{{ $golongan }}</option>
                                @endif
                                @endforeach
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
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="pendidikan_terakhir" class="form-label">Pendidikan Terakhir
                                <span class="text-danger">*</span>
                            </label>
                            @php
                            $pendidikanOptions = [
                            'SD 小学' => 'SD 小学',
                            'SMP 初中' => 'SMP 初中',
                            'SMA 高中' => 'SMA 高中',
                            'SMK 高中' => 'SMK 高中',
                            'D3 大专三年' => 'D3 大专三年',
                            'D4 大专三年' => 'D4 大专三年',
                            'S1 本科' => 'S1 本科',
                            'S2 研究生' => 'S2 研究生',
                            ];
                            $selectedPendidikan = $biodata->pendidikan_terakhir ?? '';
                            @endphp
                            <select class="form-select" name="pendidikan_terakhir" required>
                                <option value="">Pilih pendidikan terakhir</option>
                                @foreach($pendidikanOptions as $value => $label)
                                <option value="{{ $value }}" {{ $selectedPendidikan === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nama_instansi" class="form-label">Nama Sekolah/Kampus
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="nama_instansi" value="{{ $biodata->nama_instansi ?? '' }}" required>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="jurusan" class="form-label">Jurusan
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="jurusan" value="{{ $biodata->jurusan ?? '' }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nilai_ipk" class="form-label">Nilai Akhir
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="nilai_ipk" id="nilai_ipk" value="{{ $biodata->nilai_ipk ?? '' }}" required>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label>Tahun lulus
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="tahun_lulus" class="form-control" value="{{ $biodata->tahun_lulus ?? '' }}">
                            </div>
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
                            @php
                            $statusOptions = [
                            'Belum Kawin' => 'Belum Kawin',
                            'Kawin' => 'Kawin',
                            'Cerai Hidup' => 'Cerai Hidup',
                            'Cerai Mati' => 'Cerai Mati'
                            ];
                            $selectedStatus = $biodata->status_pernikahan ?? '';
                            @endphp
                            <select name="status_pernikahan" class="form-select" id="status_pernikahan" required>
                                <option value="">Pilih status pernikahan</option>
                                @if($selectedStatus && !array_key_exists($selectedStatus, $statusOptions))
                                <option value="{{ $selectedStatus }}" selected>{{ $selectedStatus }}</option>
                                @endif
                                @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" {{ $selectedStatus === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
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
                        <input type="tel"
                            name="no_telp_darurat"
                            class="form-control"
                            value="{{ $biodata->no_telepon_darurat ?? '' }}"
                            pattern="^(?:\+62|62|0)[2-9][0-9]{7,11}$"
                            title="Masukkan nomor telepon Indonesia yang valid (misalnya 08123456789 atau +628123456789)"
                            required>
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
                            <option value="Pasangan">Pasangan</option>
                            <option value="Saudara">Saudara</option>
                            <option value="Sepupu">Sepupu</option>
                            <option value="Teman Dekat">Teman</option>
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

                    <div class="row g-3 wow fadeInDown" data-wow-delay="0.2s" style="visibility: visible; animation-delay: 0.4s; animation-name: fadeInDown;">

                        @php
                        $dokumenFields = [
                        'cv' => ['label' => 'CV (pdf)', 'accept' => '.pdf'],
                        'pas_foto' => ['label' => 'Pas Foto 3x4 (jpeg, jpg, png)', 'accept' => '.png,.jpg,.jpeg'],
                        'surat_lamaran' => ['label' => 'Surat Lamaran Kerja (pdf)', 'accept' => '.pdf'],
                        'ijazah' => ['label' => 'Ijazah dan Transkrip nilai (pdf)', 'accept' => '.pdf'],
                        'ktp' => ['label' => 'Kartu Tanda Penduduk (KTP) (jpg, jpeg, png)', 'accept' => '.jpg,.jpeg,.png'],
                        'sim_b_2' => ['label' => 'SIM B II Umum/SIO (jpg, jpeg, png) <sup>Opsional</sup>', 'accept' => '.jpg,.jpeg,.png'],
                        'skck' => ['label' => 'SKCK (pdf)', 'accept' => '.pdf'],
                        'sertifikat_vaksin' => ['label' => 'Sertifikat Vaksin (pdf)', 'accept' => '.pdf'],
                        'kartu_keluarga' => ['label' => 'Kartu Keluarga (pdf)', 'accept' => '.pdf'],
                        'npwp' => ['label' => 'NPWP (pdf)', 'accept' => '.pdf'],
                        'ak1' => ['label' => 'Kartu Pencari Kejra (AK1) (pdf)', 'accept' => '.pdf'],
                        'sertifikat_pendukung' => ['label' => 'Sertifikat Pendukung (pdf)', 'accept' => '.pdf'],
                        ];
                        @endphp

                        <div class="row g-3 wow fadeInDown" data-wow-delay="0.2s" style="visibility: visible; animation-delay: 0.4s; animation-name: fadeInDown;">
                            @foreach($dokumenFields as $field => $meta)
                            @php
                            $filename = $biodata->$field ?? null;
                            $label = $meta['label'];
                            $accept = $meta['accept'];
                            $inputId = $field . '-upload';
                            $spanId = 'file-name-' . str_replace('_', '-', $field);
                            @endphp

                            @if(!$biodata || !$filename)
                            <div class="col-md-6 mb-2">
                                <label class="form-label">{!! $label !!}</label>
                                <div class="file-upload-box">
                                    <div class="upload-label">
                                        <i class="bi bi-file-earmark-text file-icon"></i>
                                        <span id="{{ $spanId }}">Dokumen belum diunggah</span>
                                    </div>
                                    <label for="{{ $inputId }}" class="btn btn-upload">Unggah</label>
                                    <input type="file" name="{{ $field }}" id="{{ $inputId }}" accept="{{ $accept }}">
                                </div>
                            </div>
                            @else
                            <div class="col-md-6 mb-2">
                                <label class="form-label">{!! strip_tags($label) !!}</label>
                                <div class="file-box">
                                    <div class="file-info">
                                        <i class="bi bi-file-earmark-text file-icon"></i>
                                        <div class="file-meta">
                                            <span class="file-name-{{ str_replace('_', '-', $field) }}">{{ $filename }}</span>
                                            <input type="hidden" name="{{ $field }}" value="{{ $filename }}">
                                        </div>
                                    </div>
                                    <div class="btn-group-custom">
                                        <a href="{{ asset(Auth::user()->no_ktp . '/dokumen/' . $filename) }}" target="_blank" class="btn btn-view">Lihat</a>
                                        <input type="file" name="{{ $field }}" id="{{ $inputId }}" value="{{ $filename }}">
                                        <button type="button"
                                            class="btn btn-delete btn-confirm-delete"
                                            data-url="{{ route('biodata.deleteFile', ['field' => $field]) }}"
                                            data-field="{{ $field }}">
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </div>
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
    document.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.getElementById('sesuaiAlamatKtp');
        const alamatDomisili = document.getElementById('alamatDomisiliField');

        function toggleAlamatDomisili() {
            alamatDomisili.style.display = checkbox.checked ? 'none' : 'block';
        }

        // Initial toggle
        toggleAlamatDomisili();

        // Event listener on checkbox
        checkbox.addEventListener('change', toggleAlamatDomisili);
    });

    document.addEventListener('DOMContentLoaded', function() {
        const nilaiInput = document.getElementById('nilai_ipk');

        nilaiInput.addEventListener('blur', function() {
            let rawVal = nilaiInput.value.replace(',', '').replace('.', ''); // Hilangkan koma/titik

            // Hanya ambil digit angka
            let numericOnly = rawVal.replace(/\D/g, '');

            if (numericOnly.length >= 2) {
                // Sisipkan koma setelah digit pertama, contoh: 396 -> 3,96
                let result = numericOnly.slice(0, 1) + ',' + numericOnly.slice(1, 3);
                nilaiInput.value = result;
            } else if (numericOnly.length === 1) {
                // Contoh: 3 -> 3,00
                nilaiInput.value = numericOnly + ',00';
            } else {
                nilaiInput.value = '';
            }
        });
    });

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

        var hash = window.location.hash;
        if (hash) {
            var tabTriggerEl = document.querySelector('[data-bs-target="' + hash + '"]');
            if (tabTriggerEl) {
                var tab = new bootstrap.Tab(tabTriggerEl);
                tab.show();

                // Update currentStep sesuai index tab hash
                tabs.forEach((t, i) => {
                    if (t.getAttribute('data-bs-target') === hash) {
                        currentStep = i;
                    }
                });
            }
        }

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

        // Scroll ke field/input/form teratas pada tab-pane aktif
        setTimeout(() => {
            const startElement = document.getElementById('start');
            // Atau jika ingin scroll ke elemen tersebut:
            if (startElement) {
                startElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }, 200);
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

    document.addEventListener('DOMContentLoaded', function() {
        const dokumenFields = @json(array_keys($dokumenFields));

        dokumenFields.forEach(field => {
            const inputId = `${field}-upload`;
            const spanId = `file-name-${field.replace(/_/g, '-')}`;

            const input = document.getElementById(inputId);
            const span = document.getElementById(spanId);

            if (input && span) {
                input.addEventListener('change', function() {
                    const fileName = input.files[0]?.name || 'Dokumen belum diunggah';
                    span.textContent = fileName;
                });
            }
        });
    });

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