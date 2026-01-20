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

    .check-pasif {
        pointer-events: none;
    }

    .header {
        text-align: center;
        border-bottom: 3px solid #2c3e50;
        padding-bottom: 20px;
        margin-bottom: 30px;
    }

    .header h1 {
        color: #2c3e50;
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 10px;
    }

    .section {
        margin-bottom: 30px;
    }

    .section-title {
        background: linear-gradient(135deg, #2c3e50, #3498db);
        color: white;
        padding: 12px 20px;
        font-size: 16px;
        font-weight: bold;
        margin-bottom: 15px;
        border-radius: 5px;
    }

    .subsection {
        margin-bottom: 20px;
    }

    .subsection-title {
        background-color: #ecf0f1;
        color: #2c3e50;
        padding: 8px 15px;
        font-weight: bold;
        margin-bottom: 10px;
        border-left: 4px solid #3498db;
    }

    .requirement-list {
        margin-left: 20px;
        margin-bottom: 15px;
    }

    .requirement-list li {
        margin-bottom: 8px;
        text-align: justify;
    }

    .legal-text {
        background-color: #fafafa;
        border-left: 4px solid #e74c3c;
        padding: 15px;
        margin: 15px 0;
        font-style: italic;
        border-radius: 0 5px 5px 0;
    }

    .checkbox-section {
        background-color: #f8f9fa;
        border: 2px solid #dee2e6;
        padding: 20px;
        margin: 15px 0;
        border-radius: 8px;
    }

    .checkbox-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 15px;
        padding: 10px;
        background: white;
        border-radius: 5px;
        border: 1px solid #e9ecef;
    }

    .checkbox-item input[type="checkbox"] {
        margin-right: 15px;
        margin-top: 5px;
        transform: scale(1.2);
    }

    .checkbox-item label {
        cursor: pointer;
        text-align: justify;
        flex: 1;
    }

    .warning-box {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: white;
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
    }

    .warning-box h3 {
        margin-bottom: 10px;
        font-size: 18px;
    }

    .article-reference {
        background-color: #e8f4f8;
        border: 1px solid #bee5eb;
        padding: 15px;
        margin: 10px 0;
        border-radius: 5px;
        font-size: 14px;
    }

    .signature-section {
        margin-top: 40px;
        padding-top: 30px;
        border-top: 2px solid #2c3e50;
    }

    .signature-box {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        margin-top: 30px;
    }

    .signature-field {
        text-align: center;
        padding: 20px;
        border: 2px dashed #bdc3c7;
        border-radius: 8px;
    }

    .signature-field h4 {
        margin-bottom: 60px;
        color: #2c3e50;
    }

    .signature-line {
        border-top: 2px solid #2c3e50;
        margin-top: 20px;
        padding-top: 10px;
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
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#step6" type="button">Syarat dan Ketentuan</button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Step Biodata -->
                <div class="tab-pane fade show active" id="step1">
                    <h6 class="text-primary">Biodata</h6>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Nama <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control" value="{{ Auth::user()->name }}" readonly>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>No KTP <span class="text-danger">*</span></label>
                            <input type="text" name="no_ktp" class="form-control" value="{{ Auth::user()->no_ktp }}" readonly>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>No Telp <span class="text-danger">*</span></label>
                            <input type="tel" name="no_telp"
                                class="form-control"
                                value="{{ old('no_telp', $biodata->no_telp ?? '') }}"
                                pattern="^(?:\+62|62|0)[2-9][0-9]{7,11}$"
                                required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>No Kartu Keluarga <span class="text-danger">*</span></label>
                            <input type="text" name="no_kk"
                                class="form-control"
                                maxlength="16"
                                value="{{ old('no_kk', $biodata->no_kk ?? '') }}"
                                required>
                        </div>
                    </div>

                    @php
                    $selectedGender = old('jenis_kelamin', $biodata->jenis_kelamin ?? '');
                    @endphp
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="">Pilih jenis kelamin</option>
                                <option value="M 男" {{ $selectedGender == 'M 男' ? 'selected' : '' }}>Laki-laki 男</option>
                                <option value="F 女" {{ $selectedGender == 'F 女' ? 'selected' : '' }}>Perempuan 女</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>NPWP <span class="text-danger">*</span></label>
                            <input type="text" name="no_npwp"
                                class="form-control"
                                maxlength="20"
                                value="{{ old('no_npwp', $biodata->no_npwp ?? '') }}"
                                required>
                        </div>
                    </div>

                    @php
                    $selectedAgama = old('agama', $biodata->agama ?? '');
                    $selectedVaksin = old('vaksin', $biodata->vaksin ?? '');
                    @endphp
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Agama <span class="text-danger">*</span></label>
                            <select name="agama" class="form-select" required>
                                <option value="">Pilih agama</option>
                                @foreach(['ISLAM 伊斯兰教','KRISTEN PROTESTAN 基督教新教','KRISTEN KATHOLIK 天主教徒','BUDHA 佛教','HINDU 印度教','KHONGHUCU 儒教'] as $agama)
                                <option value="{{ $agama }}" {{ $selectedAgama == $agama ? 'selected' : '' }}>
                                    {{ $agama }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Vaksin <span class="text-danger">*</span></label>
                            <select name="vaksin" class="form-select" required>
                                <option value="">Pilih vaksin</option>
                                @foreach(['VAKSIN 1','VAKSIN 2','VAKSIN 3'] as $vaksin)
                                <option value="{{ $vaksin }}" {{ $selectedVaksin == $vaksin ? 'selected' : '' }}>
                                    {{ $vaksin }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <h6 class="text-primary">Alamat KTP</h6>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Tempat Lahir <span class="text-danger">*</span></label>
                            <input type="text" name="tempat_lahir"
                                class="form-control"
                                value="{{ old('tempat_lahir', $biodata->tempat_lahir ?? '') }}"
                                required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Tanggal Lahir <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_lahir"
                                class="form-control"
                                value="{{ old('tanggal_lahir', $biodata->tanggal_lahir ?? '') }}"
                                required>
                        </div>
                    </div>

                    <input type="hidden" id="is_edit" value="{{ $biodata ? 1 : 0 }}">

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Provinsi
                                <span class="text-danger">*</span>
                            </label>
                            <select name="provinsi" id="provinsi_id" class="form-select" required>
                                @if($biodata && $biodata->kelurahan)
                                <option value="{{ $biodata->provinsi }}">{{ $biodata->getProvinsi->provinsi }}</option>
                                @else
                                <option value="" disabled selected>Pilih provinsi</option>
                                @endif
                                @foreach ($provinsis as $item)
                                <option value="{{ $item->id }}">{{ $item->provinsi }}</option>
                                @endforeach
                            </select>
                            @error('provinsi')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Kabupaten
                                <span class="text-danger">*</span>
                            </label>
                            <select name="kabupaten" id="kabupaten_id" class="form-select" required>
                                @if($biodata && $biodata->kabupaten)
                                <option value="{{ $biodata->kabupaten }}">{{ $biodata->getKabupaten->kabupaten }}</option>
                                @else
                                <option value="" disabled selected>Pilih kabupaten</option>
                                @endif
                            </select>
                            @error('kabupaten')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Kecamatan
                                <span class="text-danger">*</span>
                            </label>
                            <select name="kecamatan" id="kecamatan_id" class="form-select" required>
                                @if($biodata && $biodata->kecamatan)
                                <option value="{{ $biodata->kecamatan }}">{{ $biodata->getKecamatan->kecamatan }}</option>
                                @else
                                <option value="" disabled selected>Pilih kecamatan</option>
                                @endif
                            </select>
                            @error('kecamatan')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Kelurahan/Desa
                                <span class="text-danger">*</span>
                            </label>
                            <select name="kelurahan" id="kelurahan_id" class="form-select" required>
                                @if($biodata && $biodata->kelurahan)
                                <option value="{{ $biodata->kelurahan }}">{{ $biodata->getKelurahan->kelurahan }}</option>
                                @else
                                <option value="" disabled selected>Pilih kelurahan</option>
                                @endif
                            </select>
                            @error('kelurahan')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Alamat Lengkap <span class="text-danger">*</span></label>
                            <textarea name="alamat" class="form-control" required>{{ old('alamat', $biodata->alamat ?? '') }}</textarea>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>Kode Pos <span class="text-danger">*</span></label>
                            <input type="text" name="kode_pos" maxlength="5"
                                class="form-control"
                                value="{{ old('kode_pos', $biodata->kode_pos ?? '') }}" required>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>RT <span class="text-danger">*</span></label>
                            <input type="text" name="rt" maxlength="3"
                                class="form-control"
                                value="{{ old('rt', $biodata->rt ?? '') }}" required>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>RW <span class="text-danger">*</span></label>
                            <input type="text" name="rw" maxlength="3"
                                class="form-control"
                                value="{{ old('rw', $biodata->rw ?? '') }}" required>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="sesuaiAlamatKtp">
                        <label class="form-check-label">Alamat KTP sesuai dengan domisili</label>
                    </div>

                    <div id="alamatDomisiliField" style="display:none">
                        <label>Alamat Domisili</label>
                        <textarea name="alamat_domisili" class="form-control">
                        {{ old('alamat_domisili', $biodata->alamat_domisili ?? '') }}
                        </textarea>
                    </div>

                    <h6 class="text-primary mt-3">Lain-lain</h6>

                    @php
                    $selectedGolongan = old('golongan_darah', $biodata->golongan_darah ?? '');
                    @endphp
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Hobi <span class="text-danger">*</span> </label>
                            <input type="text" name="hobi"
                                class="form-control"
                                value="{{ old('hobi', $biodata->hobi ?? '') }}" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Golongan Darah <span class="text-danger">*</span> </label>
                            <select name="golongan_darah" class="form-select" required>
                                <option value="">Pilih</option>
                                @foreach(['A 型','B 型','O 型','AB 型'] as $gol)
                                <option value="{{ $gol }}" {{ $selectedGolongan == $gol ? 'selected' : '' }}>
                                    {{ $gol }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Tinggi Badan (cm) <span class="text-danger">*</span> </label>
                            <input type="number" name="tinggi_badan" maxlength="3"
                                class="form-control"
                                value="{{ old('tinggi_badan', $biodata->tinggi_badan ?? '') }}"
                                min="0" max="999" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Berat Badan (kg) <span class="text-danger">*</span></label>
                            <input type="number" name="berat_badan" maxlength="3"
                                class="form-control"
                                value="{{ old('berat_badan', $biodata->berat_badan ?? '') }}"
                                min="0" max="999" required>
                        </div>
                    </div>
                </div>

                <!-- Step Pendidikan -->
                <div class="tab-pane fade" id="step2">
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Pendidikan Terakhir <span class="text-danger">*</span>
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

                            $selectedPendidikan = old(
                            'pendidikan_terakhir',
                            $biodata->pendidikan_terakhir ?? ''
                            );
                            @endphp

                            <select class="form-select" name="pendidikan_terakhir" required>
                                <option value="">Pilih pendidikan terakhir</option>
                                @foreach($pendidikanOptions as $value => $label)
                                <option value="{{ $value }}"
                                    {{ $selectedPendidikan === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Nama Sekolah / Kampus <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                class="form-control"
                                name="nama_instansi"
                                value="{{ old('nama_instansi', $biodata->nama_instansi ?? '') }}"
                                required>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Jurusan <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                class="form-control"
                                name="jurusan"
                                value="{{ old('jurusan', $biodata->jurusan ?? '') }}"
                                required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Nilai Akhir / IPK <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                class="form-control"
                                name="nilai_ipk"
                                id="nilai_ipk"
                                value="{{ old('nilai_ipk', $biodata->nilai_ipk ?? '') }}"
                                required>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>
                                Tahun Lulus <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                name="tahun_lulus"
                                class="form-control"
                                value="{{ old('tahun_lulus', $biodata->tahun_lulus ?? '') }}"
                                required>
                        </div>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label>Prestasi</label>
                        <textarea
                            name="prestasi"
                            rows="5"
                            class="form-control">{{ old('prestasi', $biodata->prestasi ?? '') }}</textarea>
                    </div>
                </div>

                <!-- Step Keluarga -->
                <div class="tab-pane fade" id="step3">
                    <h6 class="text-primary">Nama Orang Tua</h6>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Nama Ibu <span class="text-danger">*</span></label>
                            <input type="text"
                                name="nama_ibu"
                                class="form-control"
                                value="{{ old('nama_ibu', $biodata->nama_ibu ?? '') }}"
                                required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Nama Ayah <span class="text-danger">*</span></label>
                            <input type="text"
                                name="nama_ayah"
                                class="form-control"
                                value="{{ old('nama_ayah', $biodata->nama_ayah ?? '') }}"
                                required>
                        </div>
                    </div>

                    <h6 class="text-primary">Status Pernikahan</h6>

                    @php
                    $statusOptions = [
                    'Belum Kawin' => 'Belum Kawin',
                    'Kawin' => 'Kawin',
                    'Cerai Hidup' => 'Cerai Hidup',
                    'Cerai Mati' => 'Cerai Mati'
                    ];

                    $selectedStatus = old(
                    'status_pernikahan',
                    $biodata->status_pernikahan ?? ''
                    );
                    @endphp

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Status Pernikahan <span class="text-danger">*</span></label>
                            <select name="status_pernikahan"
                                id="status_pernikahan"
                                class="form-select"
                                required>

                                <option value="">Pilih status pernikahan</option>

                                @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}"
                                    {{ $selectedStatus === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Tanggal Pernikahan</label>
                            <input type="date"
                                name="tanggal_nikah"
                                id="tanggal_nikah"
                                class="form-control"
                                value="{{ old('tanggal_nikah', $biodata->tanggal_nikah ?? '') }}">
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Nama Suami / Istri</label>
                        <input type="text"
                            name="nama_pasangan"
                            class="form-control"
                            value="{{ old('nama_pasangan', $biodata->nama_pasangan ?? '') }}">
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Jumlah Anak</label>
                            <input type="number"
                                name="jumlah_anak"
                                class="form-control"
                                max="3"
                                value="{{ old('jumlah_anak', $biodata->jumlah_anak ?? '') }}">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Nama Anak ke-1</label>
                            <input type="text"
                                name="nama_anak_1"
                                class="form-control"
                                value="{{ old('nama_anak_1', $biodata->nama_anak_1 ?? '') }}">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Nama Anak ke-2</label>
                            <input type="text"
                                name="nama_anak_2"
                                class="form-control"
                                value="{{ old('nama_anak_2', $biodata->nama_anak_2 ?? '') }}">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Nama Anak ke-3</label>
                            <input type="text"
                                name="nama_anak_3"
                                class="form-control"
                                value="{{ old('nama_anak_3', $biodata->nama_anak_3 ?? '') }}">
                        </div>
                    </div>
                </div>

                <!-- Step Kontak Darurat -->
                <div class="tab-pane fade" id="step4">

                    <div class="col-md-6 mb-3">
                        <label>Nama kontak darurat <span class="text-danger">*</span></label>
                        <input type="text"
                            name="nama_kontak_darurat"
                            class="form-control"
                            value="{{ old('nama_kontak_darurat', $biodata->nama_kontak_darurat ?? '') }}"
                            required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>No telepon <span class="text-danger">*</span></label>
                        <input type="tel"
                            name="no_telp_darurat"
                            class="form-control"
                            value="{{ old('no_telp_darurat', $biodata->no_telepon_darurat ?? '') }}"
                            pattern="^(?:\+62|62|0)[2-9][0-9]{7,11}$"
                            title="Masukkan nomor telepon Indonesia yang valid (misalnya 08123456789 atau +628123456789)"
                            required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Status hubungan <span class="text-danger">*</span></label>
                        <select name="status_hubungan" class="form-select" required>
                            <option value="">Pilih status hubungan</option>

                            @php
                            $statusHubungan = old('status_hubungan', $biodata->status_hubungan ?? '');
                            @endphp

                            <option value="Orang Tua" {{ $statusHubungan == 'Orang Tua' ? 'selected' : '' }}>Orang Tua</option>
                            <option value="Pasangan" {{ $statusHubungan == 'Pasangan' ? 'selected' : '' }}>Pasangan</option>
                            <option value="Saudara" {{ $statusHubungan == 'Saudara' ? 'selected' : '' }}>Saudara</option>
                            <option value="Sepupu" {{ $statusHubungan == 'Sepupu' ? 'selected' : '' }}>Sepupu</option>
                            <option value="Teman Dekat" {{ $statusHubungan == 'Teman Dekat' ? 'selected' : '' }}>Teman</option>
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
                        'ktp' => ['label' => 'Kartu Tanda Penduduk (KTP) (jpg, jpeg, png)', 'accept' => '.jpg,.jpeg,.png', 'onchange' => 'handleKtpOCR(this)'],
                        'sim_b_2' => ['label' => 'SIM B II Umum (jpg, jpeg, png) <sup>wajib bagi pelamar DT/OPR</sup>', 'accept' => '.jpg,.jpeg,.png', 'onchange' => 'handleSimB2OCR(this)'],
                        'skck' => ['label' => 'SKCK (pdf)', 'accept' => '.pdf'],
                        'sio' => ['label' => 'SIO (jpeg, jpg, png) <sup>wajib bagi pelamar DT/OPR</sup>', 'accept' => '.png,.jpg,.jpeg'],
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
                            $ocrResultId = 'ocr-result-' . str_replace('_', '-', $field); // Tambahan
                            @endphp

                            <div class="col-md-6 mb-2">
                                <label class="form-label">{!! strip_tags($label, '<sup>') !!}</label>

                                @if(!$biodata || !$filename)
                                <div class="file-upload-box">
                                    <div class="upload-label">
                                        <i class="bi bi-file-earmark-text file-icon"></i>
                                        <span id="{{ $spanId }}">{{ $filename ? $filename : 'Dokumen belum diunggah' }}</span>
                                    </div>
                                    <label for="{{ $inputId }}" class="btn btn-upload">Unggah</label>
                                    <input type="file" name="{{ $field }}" id="{{ $inputId }}" accept="{{ $accept }}" data-accept="{{ $accept }}"
                                        onchange="uploadDocumentAjax(this)">
                                </div>
                                @if($field === 'sertifikat_pendukung')
                                <span class="small text-muted fw-bold d-block mt-1">
                                    <i class="bi bi-exclamation-circle me-1"></i>
                                    Sertifikat lebih dari 1? Gabungkan semua dalam satu file PDF sebelum diunggah!
                                </span>
                                @endif
                                {{-- Tempat hasil OCR ditampilkan hanya untuk KTP dan SIM B2 --}}
                                @if(in_array($field, ['ktp', 'sim_b_2']))
                                <div id="{{ $ocrResultId }}" class="mt-2 small text-muted">Hasil baca dokumen akan muncul di sini.</div>
                                @endif
                                @else
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
                                        @if($field === 'ktp' && $biodata->isValidOcrKtp())
                                        <button type="button"
                                            class="btn btn-delete disabled"
                                            disabled
                                            title="Dokumen KTP tidak dapat dihapus karena OCR sudah valid">
                                            Terkunci
                                        </button>
                                        <small class="text-danger d-block mt-1">
                                            <i class="bi bi-lock-fill me-1"></i>
                                            Dapat diganti setelah 1 jam
                                        </small>
                                        @else
                                        <button type="button"
                                            class="btn btn-delete btn-confirm-delete"
                                            data-url="{{ route('biodata.deleteFile', ['field' => $field]) }}"
                                            data-field="{{ $field }}">
                                            Hapus
                                        </button>
                                        @endif
                                    </div>
                                </div>
                                @if($field === 'sertifikat_pendukung')
                                <span class="small text-muted fw-bold d-block mt-1">
                                    <i class="bi bi-exclamation-circle me-1"></i>
                                    Sertifikat lebih dari 1? Gabungkan semua dalam satu file PDF sebelum diunggah!
                                </span>
                                @endif
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div id="ocr-compare-result" class="mt-3"></div>
                </div>

                <!-- Step 6 -->
                <div class="tab-pane fade" id="step6">
                    <div class="row g-3">
                        <!-- IFRAME ditampilkan penuh -->
                        <div class="col-12">
                            <div id="termsBox" style="height: 600px; overflow-y: auto; border: 1px solid #ccc; padding: 15px; background-color: #ffffff;">

                                <div class="header">
                                    <h1>SYARAT DAN KETENTUAN REKRUTMEN KERJA</h1>
                                </div>

                                <!-- BAGIAN I: SYARAT REKRUTMEN -->
                                <div class="section">
                                    <div class="section-title">I. SYARAT REKRUTMEN</div>
                                    <p style="margin-bottom: 20px;"><strong>Setiap Pelamar wajib memenuhi syarat-syarat berikut:</strong></p>

                                    <div class="subsection">
                                        <div class="subsection-title">A. SYARAT UMUM</div>
                                        <ol class="requirement-list">
                                            <li>Warga Negara Indonesia yang memiliki Kartu Tanda Penduduk/KTP aktif;</li>
                                            <li>Berusia minimal 18 tahun dan maksimal sesuai ketentuan posisi yang dilamar;</li>
                                            <li>Memiliki ijazah pendidikan sesuai kualifikasi yang dipersyaratkan;</li>
                                            <li>Sehat jasmani dan rohani;</li>
                                            <li>Tidak pernah terlibat dalam tindak pidana yang dibuktikan dengan Surat Keterangan Catatan Kepolisian/SKCK;</li>
                                            <li>Memiliki kartu/akun Badan Penyelenggara Jaminan Sosial/BPJS Kesehatan dan Ketenagakerjaan yang aktif atau bersedia dibuatkan oleh Perusahaan.</li>
                                        </ol>
                                    </div>

                                    <div class="subsection">
                                        <div class="subsection-title">B. SYARAT KHUSUS</div>
                                        <ol class="requirement-list">
                                            <li>Bersedia menjalani medical check-up menyeluruh yang dipersyaratkan;</li>
                                            <li>Tidak memiliki riwayat penyakit yang dapat membahayakan keselamatan kerja, dan sanggup bekerja di lingkungan industri peleburan nikel;</li>
                                            <li>Tidak memiliki catatan kriminal atau sedang dalam proses hukum apa pun;</li>
                                            <li>Bersedia dilakukan background check oleh pihak ketiga yang ditunjuk perusahaan;</li>
                                            <li>Tidak sedang terikat kontrak kerja dengan perusahaan lain;</li>
                                            <li>Bukan pengguna narkoba, psikotropika, dan zat adiktif lainnya serta bersedia menjalani tes urine/darah kapan saja.</li>
                                        </ol>
                                    </div>

                                    <div class="subsection">
                                        <div class="subsection-title">C. SYARAT DOKUMEN</div>
                                        <ol class="requirement-list">
                                            <li>Surat lamaran kerja;</li>
                                            <li>Curriculum Vitae (CV) terkini;</li>
                                            <li>Pas foto terbaru;</li>
                                            <li>Kartu Tanda Penduduk/KTP yang masih berlaku;</li>
                                            <li>Ijazah dan transkrip nilai pendidikan terakhir sesuai yang dipersyaratkan;</li>
                                            <li>Surat Keterangan Catatan Kepolisian/SKCK;</li>
                                            <li>Kartu Kuning/AK1/Kartu Pencari Kerja;</li>
                                            <li>Surat Izin Mengemudi/SIM (khusus posisi tertentu);</li>
                                            <li>Sertifikat keahlian (khusus posisi tertentu);</li>
                                            <li>Sertifikat pelatihan atau kompetensi (khusus posisi tertentu).</li>
                                        </ol>
                                    </div>
                                </div>

                                <!-- BAGIAN II: KETENTUAN REKRUTMEN -->
                                <div class="section">
                                    <div class="section-title">II. KETENTUAN REKRUTMEN</div>

                                    <div class="subsection">
                                        <div class="subsection-title">A. KETENTUAN UMUM</div>
                                        <p style="margin-bottom: 15px;">Berdasarkan asas-asas hukum perjanjian dan ketentuan peraturan perundang-undangan yang berlaku, ditetapkan ketentuan sebagai berikut:</p>

                                        <div style="margin-bottom: 20px;">
                                            <h4 style="color: #2c3e50; margin-bottom: 10px;">1. Prinsip Kebenaran Mutlak (Principle of Absolute Truth):</h4>
                                            <p style="text-align: justify; margin-bottom: 10px;">Bahwa setiap dan seluruh data, informasi, dokumen, keterangan, maupun pernyataan yang disampaikan oleh Pelamar kepada Perusahaan haruslah benar secara faktual, lengkap tanpa pengurangan, dan akurat sesuai dengan keadaan sebenarnya, sebagaimana diamanatkan dalam Pasal 1338 ayat (3) Kitab Undang-Undang Hukum Perdata.</p>
                                            <div class="article-reference">
                                                <strong>Pasal 1338 ayat (3) KUHPerdata:</strong> "(3) Persetujuan harus dilaksanakan dengan itikad baik"
                                            </div>
                                        </div>

                                        <div style="margin-bottom: 20px;">
                                            <h4 style="color: #2c3e50; margin-bottom: 10px;">2. Hak Prerogatif Verifikasi (Prerogative Right of Verification):</h4>
                                            <p style="text-align: justify; margin-bottom: 10px;">Perusahaan memiliki hak mutlak dan tidak dapat diganggu gugat untuk melakukan verifikasi, investigasi, konfirmasi, dan/atau pemeriksaan terhadap kebenaran, keabsahan, dan keakuratan seluruh data yang diberikan oleh Pelamar, termasuk namun tidak terbatas pada: pemeriksaan silang dengan pihak ketiga, lembaga pendidikan, instansi pemerintah, dan/atau pihak-pihak lain yang relevan.</p>
                                            <div class="article-reference">
                                                <strong>Pasal 29 UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi:</strong> "(1) Pengendali Data Pribadi wajib memastikan akurasi, kelengkapan, dan konsistensi Data Pribadi sesuai dengan ketentuan peraturan perundang-undangan; (2) Dalam memastikan akurasi, kelengkapan, dan konsistensi Data Pribadi sebagaimana dimaksud pada ayat (1) Pengendali Data Pribadi wajib melakukan verifikasi"
                                            </div>
                                        </div>

                                        <div style="margin-bottom: 20px;">
                                            <h4 style="color: #2c3e50; margin-bottom: 10px;">3. Tanggung Jawab Mutlak (Strict Liability):</h4>
                                            <p style="text-align: justify; margin-bottom: 10px;">Pelamar memikul tanggung jawab hukum secara mutlak (strict liability) atas kebenaran, keabsahan, dan keakuratan seluruh informasi yang telah disampaikan, tanpa dapat berdalih ketidaktahuan, kelalaian, atau sebab-sebab lain yang dapat menghapuskan tanggung jawab tersebut.</p>
                                            <div class="article-reference">
                                                <strong>Pasal 1366 KUHPerdata:</strong> "Setiap orang bertanggung jawab, bukan hanya atas kerugian yang disebabkan perbuatan-perbuatan, melainkan juga atas kerugian yang disebabkan kelalaian atau kesembronoannya"
                                            </div>
                                        </div>

                                        <div style="margin-bottom: 20px;">
                                            <h4 style="color: #2c3e50; margin-bottom: 10px;">4. Prinsip Kepatuhan Regulatif (Regulatory Compliance Principle):</h4>
                                            <p style="text-align: justify;">Pelamar wajib patuh dan tunduk secara penuh kepada seluruh peraturan perundang-undangan Negara Kesatuan Republik Indonesia dan asas-asas hukum universal yang berlaku, serta seluruh syarat, ketentuan, dan kebijakan proses rekrutmen Perusahaan.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- BAGIAN III: REGIMEN SANKSI PELANGGARAN -->
                                <div class="section">
                                    <div class="section-title">III. REGIMEN SANKSI PELANGGARAN</div>
                                    <div class="warning-box">
                                        <h3>⚠️ PERINGATAN PENTING</h3>
                                        <p>Dalam hal ditemukan adanya ketidakbenaran data, pemalsuan dokumen, atau pelanggaran terhadap persyaratan yang telah ditetapkan, maka berlaku regimen sanksi sebagai berikut:</p>
                                    </div>

                                    <div class="subsection">
                                        <div class="subsection-title">A. SANKSI ADMINISTRASI</div>
                                        <div style="margin-bottom: 15px;">
                                            <h4 style="color: #e74c3c; margin-bottom: 10px;">1. Berdasarkan hak pengelolaan administratif Perusahaan:</h4>
                                            <ul class="requirement-list">
                                                <li>diskualifikasi dari seluruh tahapan proses seleksi (ex officio);</li>
                                                <li>pembatalan sepihak terhadap segala bentuk tawaran yang telah disepakati (unilateral cancellation);</li>
                                                <li>blacklisting permanen dari seluruh proses rekrutmen Perusahaan di masa yang akan datang tanpa batasan waktu.</li>
                                            </ul>
                                        </div>

                                        <div style="margin-bottom: 15px;">
                                            <h4 style="color: #e74c3c; margin-bottom: 10px;">2. Kewajiban Ganti Rugi:</h4>
                                            <p style="text-align: justify; margin-bottom: 10px;">Berdasarkan Pasal 1365 Kitab Undang-Undang Hukum Perdata, Pelamar wajib memberi restitusi integral atau ganti rugi keseluruhan (full restitution) atas segala kerugian materiil dan immateriil yang diderita Perusahaan akibat perbuatan Pelamar.</p>
                                            <div class="article-reference">
                                                <strong>Pasal 1365 KUHPerdata:</strong> "Tiap perbuatan yang melanggar hukum dan membawa kerugian kepada orang lain, mewajibkan orang yang menimbulkan kerugian itu karena kesalahannya untuk menggantikan kerugian tersebut"
                                            </div>
                                        </div>
                                    </div>

                                    <div class="subsection">
                                        <div class="subsection-title">B. SANKSI PIDANA DAN PERDATA</div>
                                        <div style="margin-bottom: 15px;">
                                            <h4 style="color: #e74c3c; margin-bottom: 10px;">1. Sanksi Pidana:</h4>
                                            <div class="article-reference">
                                                <strong>Pasal 263 KUHP:</strong> Pemalsuan surat - pidana penjara paling lama 6 tahun
                                            </div>
                                            <div class="article-reference">
                                                <strong>Pasal 266 KUHP:</strong> Keterangan palsu dalam akta otentik - pidana penjara paling lama 7 tahun
                                            </div>
                                        </div>

                                        <div style="margin-bottom: 15px;">
                                            <h4 style="color: #e74c3c; margin-bottom: 10px;">2. Sanksi Perdata:</h4>
                                            <div class="article-reference">
                                                <strong>Pasal 1365 KUHPerdata:</strong> Kewajiban mengganti kerugian akibat perbuatan melanggar hukum
                                            </div>
                                            <div class="article-reference">
                                                <strong>Pasal 1366 KUHPerdata:</strong> Tanggung jawab atas kerugian akibat kelalaian
                                            </div>
                                        </div>
                                    </div>

                                    <div class="subsection">
                                        <div class="subsection-title">C. AKIBAT HUKUM – BATAL DEMI HUKUM (NIETIGHEID VAN RECHTSWEGE)</div>
                                        <div class="legal-text">
                                            <p><strong>KONSEKUENSI HUKUM YANG TIDAK DAPAT DIBATALKAN:</strong></p>
                                            <ol style="margin-left: 20px; margin-top: 10px;">
                                                <li>Seluruh proses rekrutmen, perjanjian kerja, dan hubungan hukum yang timbul adalah <strong>BATAL DEMI HUKUM</strong> sejak semula (dianggap tidak pernah ada/ab initio);</li>
                                                <li>Perusahaan tidak memiliki kewajiban hukum untuk melakukan pemberitahuan PHK atau pembayaran pesangon (dismissal without severance pay);</li>
                                                <li>Perusahaan berhak melakukan pemotongan gaji sebagai bentuk ganti rugi.</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>

                                <!-- BAGIAN PERNYATAAN, PERSETUJUAN, DAN KESANGGUPAN -->
                                <div class="section">
                                    <div class="section-title">PERNYATAAN, PERSETUJUAN, DAN KESANGGUPAN PELAMAR KERJA</div>

                                    <div class="subsection">
                                        <div class="subsection-title">I. PERNYATAAN PATUH DAN TUNDUK PADA DASAR HUKUM</div>
                                        <p style="text-align: justify; background-color: #f8f9fa; padding: 15px; border-radius: 5px;">
                                            Dengan ini, saya, dalam kedudukan sebagai Pelamar, mengakui dan menerima secara sadar, sukarela, dan tanpa paksaan dari pihak mana pun, menyatakan patuh dan tunduk secara penuh kepada seluruh peraturan perundang-undangan Negara Kesatuan Republik Indonesia dan asas-asas hukum universal yang berlaku, serta seluruh syarat, ketentuan, dan kebijakan proses rekrutmen Perusahaan.
                                        </p>
                                    </div>

                                    <div class="subsection">
                                        <div class="subsection-title">II. KONSEKUENSI JURIDIS YANG DIAKUI DAN DITERIMA</div>
                                        <p style="margin-bottom: 15px;">Berdasarkan pada pengakuan dan penerimaan sebagaimana tersebut di atas, dengan ini saya juga memahami, mengakui, menerima, serta menyadari bahwa dalam hal Perusahaan menemukan ketidaksesuaian, kekeliruan, kesalahan, atau pemalsuan terhadap seluruh data, informasi, dokumen, keterangan, pernyataan, serta hal-hal lainnya yang telah, sedang, dan akan saya sampaikan, maka dibebankan atas saya:</p>
                                        <ol class="requirement-list">
                                            <li><strong>Pertanggungjawaban Hukum:</strong> pertanggungjawaban pidana dan/atau perdata sesuai dengan ketentuan peraturan perundang-undangan yang berlaku.</li>
                                            <li><strong>Tanggung Jawab Mutlak:</strong> akibat hukum yang timbul sebab pelanggaran yang melekat pada diri saya berdasarkan hukum (ex lege) tanpa dapat dialihkan kepada pihak lain.</li>
                                            <li><strong>Kerugian Materiil dan Immateriil:</strong> segala bentuk kerugian materiil dan immateriil yang diderita oleh Perusahaan akibat perbuatan melawan hukum yang saya lakukan menjadi tanggung jawab penuh saya untuk memberikan kompensasi yang layak dan memadai.</li>
                                            <li><strong>Beban Pembuktian Terbalik:</strong> Dalam hal terjadi sengketa, saya menerima bahwa beban pembuktian mengenai kebenaran data dan keabsahan dokumen yang saya sampaikan berada pada saya (burden of proof), bukan pada Perusahaan.</li>
                                            <li><strong>Biaya Perkara dan Proses Hukum:</strong> Seluruh biaya yang timbul dalam proses penyelesaian sengketa, termasuk namun tidak terbatas pada: biaya perkara, honor advokat, biaya saksi ahli, dan biaya-biaya lain yang terkait menjadi tanggung jawab saya sepenuhnya.</li>
                                        </ol>
                                    </div>

                                    <div class="subsection">
                                        <div class="subsection-title">III. PERSETUJUAN KESANGGUPAN</div>
                                        <p style="margin-bottom: 20px; text-align: justify; font-style: italic;">
                                            Dengan menandacentangi atau tetap mengikuti proses rekrutmen ini, saya dalam kapasitas sebagai Pelamar, dengan penuh kesadaran hukum, serta atas kehendak bebas, dan tanpa adanya paksaan, kekeliruan, kekhilafan, atau penipuan dari pihak mana pun (vrije wil, zonder dwang, dwaling, of bedrog), dengan ini secara tegas dan tidak dapat ditarik kembali, menyatakan:
                                        </p>

                                        <div class="checkbox-section">
                                            <h4 style="color: #2c3e50; margin-bottom: 15px; text-align: center;">A. PERSETUJUAN FINAL DAN MENGIKAT (FINAL AND BINDING AGREEMENT)</h4>

                                            <div class="checkbox-item">
                                                <input type="checkbox" id="agree1" class="check-pasif form-check-input" checked onclick="return false;" name="agreements">
                                                <label for="agree1">
                                                    <strong>MENYETUJUI</strong> secara mutlak seluruh syarat dan ketentuan rekrutmen yang telah ditetapkan serta persetujuan ini sebagai dokumen hukum yang sah dan mengikat (legally binding document) antara saya dengan Perusahaan sebagai Para Pihak (pacta sunt servanda), serta memiliki kekuatan eksekutorial, sehingga pelanggarannya dapat mengakibatkan sanksi hukum yang tegas sesuai dengan mekanisme penegakan hukum yang berlaku di Negara Kesatuan Republik Indonesia;
                                                </label>
                                            </div>

                                            <div class="checkbox-item">
                                                <input type="checkbox" id="agree2" class="check-pasif form-check-input" checked onclick="return false;" name="agreements">
                                                <label for="agree2">
                                                    <strong>MEMAHAMI</strong> sepenuhnya dan secara komprehensif seluruh konsekuensi hukum, baik pidana maupun perdata, yang timbul akibat dari pelanggaran yang saya lakukan terhadap syarat dan ketentuan yang telah ditetapkan;
                                                </label>
                                            </div>

                                            <div class="checkbox-item">
                                                <input type="checkbox" id="agree3" class="check-pasif form-check-input" checked onclick="return false;" name="agreements">
                                                <label for="agree3">
                                                    <strong>MENERIMA</strong> dengan sepenuh hati dan tanpa keberatan, seluruh regimen sanksi yang telah ditetapkan, dan bersedia menjalani sanksi-sanksi tersebut apabila di kemudian hari terbukti melakukan pelanggaran terhadap syarat dan ketentuan yang telah ditetapkan;
                                                </label>
                                            </div>
                                        </div>

                                        <div class="checkbox-section">
                                            <h4 style="color: #2c3e50; margin-bottom: 15px; text-align: center;">B. JAMINAN MUTLAK (ABSOLUTE WARRANTY)</h4>

                                            <div class="checkbox-item">
                                                <input type="checkbox" id="warranty1" class="check-pasif form-check-input" checked onclick="return false;" name="warranties">
                                                <label for="warranty1">
                                                    <strong>MENJAMIN</strong> dengan segenap integritas dan kehormatan diri bahwa seluruh data, informasi, dokumen, keterangan, pernyataan, serta hal-hal lainnya yang telah, sedang, dan akan saya sampaikan, terkait syarat rekrutmen kepada Perusahaan adalah benar secara faktual, lengkap tanpa pengurangan material, dan akurat sesuai dengan keadaan yang sebenarnya, serta tidak mengandung unsur penipuan, penyesatan, atau pemalsuan dalam bentuk apa pun;
                                                </label>
                                            </div>

                                            <div class="checkbox-item">
                                                <input type="checkbox" id="warranty2" class="check-pasif form-check-input" checked onclick="return false;" name="warranties">
                                                <label for="warranty2">
                                                    <strong>MENJAMIN</strong> secara absolut kesanggupan saya, baik secara fisik, mental, maupun hukum untuk memenuhi seluruh persyaratan yang telah ditetapkan oleh Perusahaan, termasuk namun tidak terbatas pada: persyaratan kesehatan, kompetensi, integritas, dan kepatuhan terhadap peraturan perundang-undangan;
                                                </label>
                                            </div>

                                            <div class="checkbox-item">
                                                <input type="checkbox" id="warranty3" class="check-pasif form-check-input" checked onclick="return false;" name="warranties">
                                                <label for="warranty3">
                                                    <strong>MENJAMIN</strong> kepatuhan penuh dan konsisten terhadap seluruh peraturan, ketentuan, prosedur, dan kebijakan rekrutmen yang telah, sedang, dan akan ditetapkan oleh Perusahaan, serta seluruh peraturan perundang-undangan yang berlaku di Negara Kesatuan Republik Indonesia;
                                                </label>
                                            </div>
                                        </div>

                                        <div class="checkbox-section">
                                            <h4 style="color: #2c3e50; margin-bottom: 15px; text-align: center;">C. PELEPASAN HAK MUTLAK (ABSOLUTE WAIVER OF RIGHTS)</h4>

                                            <div class="checkbox-item">
                                                <input type="checkbox" id="waiver1" class="check-pasif form-check-input" checked onclick="return false;" name="waivers">
                                                <label for="waiver1">
                                                    <strong>MELEPASKAN</strong> dengan sepenuhnya dengan tidak dapat ditarik kembali (irrevocable) segala hak untuk mengajukan gugatan, tuntutan, klaim, keberatan, atau bentuk upaya hukum lainnya terhadap Perusahaan, direksi, komisaris, pemegang saham, jajaran manajemen, atau afiliasi Perusahaan apabila sanksi-sanksi sebagaimana dimaksud dalam ketentuan ini diberlakukan atas pelanggaran yang telah saya lakukan;
                                                </label>
                                            </div>

                                            <div class="checkbox-item">
                                                <input type="checkbox" id="waiver2" class="check-pasif form-check-input" checked onclick="return false;" name="waivers">
                                                <label for="waiver2">
                                                    <strong>MEMBERIKAN KUASA</strong> yang sah dan tidak dapat dicabut (irrevocable power of attorney) kepada Perusahaan untuk melakukan verifikasi, investigasi, dan konfirmasi kepada seluruh pihak ketiga yang relevan, termasuk namun tidak terbatas pada: instansi pemerintah, lembaga pendidikan, perusahaan tempat bekerja sebelumnya, pemberi referensi, dan pihak-pihak lain yang dipandang perlu untuk memastikan kebenaran dan keabsahan data/informasi yang saya sampaikan;
                                                </label>
                                            </div>

                                            <div class="checkbox-item">
                                                <input type="checkbox" id="waiver3" class="check-pasif form-check-input" checked onclick="return false;" name="waivers">
                                                <label for="waiver3">
                                                    <strong>MENYETUJUI</strong> untuk tunduk pada yurisdiksi dan kompetensi absolut Pengadilan Negeri Kota Kendari untuk penyelesaian segala sengketa, perselisihan, atau konflik yang mungkin timbul dari atau sehubungan dengan pelaksanaan syarat dan ketentuan ini, dengan mengesampingkan eksepsi kewenangan relatif atau upaya untuk memindahkan yurisdiksi ke pengadilan di wilayah lain;
                                                </label>
                                            </div>
                                        </div>

                                        <div class="checkbox-section">
                                            <h4 style="color: #2c3e50; margin-bottom: 15px; text-align: center;">D. KESANGGUPAN FINAL DAN MENGIKAT (FINAL AND BINDING COMMITMENT)</h4>

                                            <div class="checkbox-item">
                                                <input type="checkbox" id="commitment1" class="check-pasif form-check-input" checked onclick="return false;" name="commitments">
                                                <label for="commitment1">
                                                    Menyatakan <strong>SANGGUP dan SIAP</strong> memikul seluruh tanggung jawab hukum (legal liability), finansial (financial obligation), dan moral (moral responsibility) yang timbul dari proses rekrutmen kerja ini, termasuk segala konsekuensi yang dapat terjadi di masa mendatang sesuai dengan ketentuan peraturan perundang-undangan yang berlaku;
                                                </label>
                                            </div>

                                            <div class="checkbox-item">
                                                <input type="checkbox" id="commitment2" class="check-pasif form-check-input" checked onclick="return false;" name="commitments">
                                                <label for="commitment2">
                                                    <strong>BERKOMITMEN</strong> untuk melaksanakan seluruh kewajiban yang timbul dari persetujuan ini dengan penuh integritas, profesionalitas, dan dedikasi yang tinggi, serta tidak akan melakukan perbuatan-perbuatan yang dapat merugikan kepentingan Perusahaan atau bertentangan dengan nilai-nilai etika dan moral;
                                                </label>
                                            </div>

                                            <div class="checkbox-item">
                                                <input type="checkbox" id="commitment3" class="check-pasif form-check-input" checked onclick="return false;" name="commitments">
                                                <label for="commitment3">
                                                    <strong>MENYADARI</strong> secara penuh seluruh syarat dan ketentuan rekrutmen yang telah ditetapkan serta persetujuan ini sebagai dokumen hukum yang sah dan mengikat (legally binding document) antara saya dengan Perusahaan sebagai Para Pihak (pacta sunt servanda), serta memiliki kekuatan eksekutorial, sehingga pelanggarannya dapat mengakibatkan sanksi hukum yang tegas sesuai dengan mekanisme penegakan hukum yang berlaku di Negara Kesatuan Republik Indonesia.
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- BAGIAN TANDA TANGAN -->
                                <div class="signature-section">
                                    <div class="warning-box">
                                        <h3>📋 PERHATIAN KHUSUS</h3>
                                        <p><strong>Dengan menyetujui dokumen ini, Pelamar menyatakan telah membaca, memahami, dan menyetujui seluruh isi dokumen serta siap menanggung segala konsekuensi hukum yang timbul.</strong></p>
                                    </div>

                                    <div style="margin-top: 30px; padding: 20px; background-color: #f8f9fa; border-radius: 8px; text-align: center;">
                                        <p style="font-style: italic; color: #6c757d; margin-bottom: 10px;">
                                            "Dokumen ini telah disusun sesuai dengan peraturan perundang-undangan yang berlaku di Negara Kesatuan Republik Indonesia"
                                        </p>
                                        <p style="font-size: 12px; color: #6c757d;">
                                            Dokumen ini dibuat dalam rangkap yang cukup dan mempunyai kekuatan hukum yang sama
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Checkbox pernyataan -->
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="checkBox1"
                                    {{ $biodata && $biodata->status_pernyataan ? 'checked' : 'disabled' }}>
                                <label class="form-check-label" for="checkBox1">
                                    Saya memahami bahwa apabila terbukti melakukan pemalsuan data, saya bersedia menerima konsekuensinya, termasuk tidak diluluskan dalam proses rekrutmen.
                                </label>
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
    function uploadDocumentAjax(input) {
        const file = input.files[0];
        if (!file) return;

        const field = input.name;
        const container = input.closest('.col-md-6');
        const uploadBox = container.querySelector('.file-upload-box');
        const fileNameSpan = container.querySelector(`#file-name-${field.replaceAll('_', '-')}`);

        // loading
        fileNameSpan.innerHTML = '⏳ Mengunggah...';

        const formData = new FormData();
        formData.append(field, file);

        fetch("{{ route('biodata.upload.document') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })
            .then(async response => {
                const data = await response.json();

                // VALIDATION / SERVER ERROR
                if (!response.ok) {
                    if (data.errors) {
                        // ambil pesan validasi pertama
                        throw Object.values(data.errors)[0][0];
                    }
                    throw data.message || 'Upload gagal';
                }

                return data;
            })
            .then(res => {
                // ====== UPDATE UI TANPA RELOAD ======
                uploadBox.innerHTML = `
        <div class="file-box">
            <div class="file-info">
                <i class="bi bi-file-earmark-text file-icon"></i>
                <div class="file-meta">
                    <span class="file-name-${field.replaceAll('_', '-')}">${res.file}</span>
                    <input type="hidden" name="${field}" value="${res.file}">
                </div>
            </div>
            <div class="btn-group-custom">
                <a href="/${res.path}" target="_blank" class="btn btn-view">Lihat</a>
                <button type="button"
                    class="btn btn-delete btn-confirm-delete"
                    data-url="{{ url('biodata/delete-file') }}/${field}"
                    data-field="${field}">
                    Hapus
                </button>
            </div>
        </div>`;

                // ====== AUTO OCR ======
                if (field === 'ktp') handleKtpOcr(input);
                if (field === 'sim_b_2') handleSimB2OCR(input);
            })
            .catch(errorMessage => {
                Swal.fire({
                    icon: 'warning',
                    text: errorMessage
                });

                fileNameSpan.innerHTML = 'Dokumen belum diunggah';
                input.value = ''; // reset input
            });
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-confirm-delete');
        if (!btn) return;

        e.preventDefault();

        const container = btn.closest('.col-md-6');
        const url = btn.dataset.url;
        const field = btn.dataset.field;
        const accept = container.dataset.accept;

        if (!confirm('Yakin ingin menghapus dokumen ini?')) return;

        fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    throw res.message || 'Gagal menghapus dokumen';
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Dokumen berhasil dihapus',
                    timer: 1500,
                    showConfirmButton: false
                });

                // ====== KEMBALIKAN KE UPLOAD BOX ======
                const container = btn.closest('.col-md-6');
                container.querySelector('.file-upload-box').innerHTML = `
                <div class="upload-label">
                    <i class="bi bi-file-earmark-text file-icon"></i>
                    <span>Dokumen belum diunggah</span>
                </div>
                <label class="btn btn-upload">
                    Unggah
                    <input type="file" name="${field}" accept="${accept}" onchange="uploadDocumentAjax(this)" hidden>
                </label>
            `;
            });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const isEdit = document.getElementById('is_edit')?.value == 1;
        if (!isEdit) return;

        const provinsi = document.getElementById('provinsi_id');
        const kabupaten = document.getElementById('kabupaten_id');
        const kecamatan = document.getElementById('kecamatan_id');
        const kelurahan = document.getElementById('kelurahan_id');

        const kabKosong = !kabupaten.value;
        const kecKosong = !kecamatan.value;
        const kelKosong = !kelurahan.value;

        // Jika salah satu kosong → kosongkan provinsi saja
        if (kabKosong || kecKosong || kelKosong) {

            // Kosongkan provinsi
            provinsi.selectedIndex = 0;

            // Reset dropdown bawah ke placeholder (opsional tapi disarankan)
            kabupaten.innerHTML = '<option value="" selected disabled>Pilih kabupaten</option>';
            kecamatan.innerHTML = '<option value="" selected disabled>Pilih kecamatan</option>';
            kelurahan.innerHTML = '<option value="" selected disabled>Pilih kelurahan</option>';

            // Trigger ulang alur dropdown
            provinsi.dispatchEvent(new Event('change'));

            provinsi.focus();
        }
    });
</script>

<script>
    function handleSimB2OCR(input) {
        const file = input.files[0];
        const resultElement = document.getElementById("ocr-result-sim-b-2");

        if (!resultElement) {
            console.error("Elemen hasil OCR tidak ditemukan");
            return;
        }

        // tampilkan spinner/loading
        resultElement.innerHTML = "⏳ Memproses baca sim...";

        const formData = new FormData();
        formData.append("sim_b_2", file);

        fetch("{{ route('biodata.ocr.sim_b2') }}", {
                method: "POST",
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(result => {
                const resultElement = document.getElementById('ocr-result-sim-b-2');
                if (!resultElement) return; // Jangan lanjut jika elemen tidak ada

                if (result.success) {
                    const data = result.data;

                    window.simOcrData = data;
                    if (window.ktpOcrData) {
                        compareSimKtpOcr(window.simOcrData, window.ktpOcrData);
                    }

                    window.simOcrData = data; // Simpan data SIM secara global
                    if (window.ktpOcrData) compareSimKtpOcr(window.simOcrData, window.ktpOcrData);

                    resultElement.innerHTML = `
                        <div class="card mt-1">
                            <div class="card-header fw-bold">Hasil Baca SIM :</div>
                            <div class="card-body">
                                <p><strong>Nama Lengkap:</strong> ${data.nama}</p>
                                <p><strong>Tempat Lahir:</strong> ${data.tempat_lahir}</p>
                                <p><strong>Tanggal Lahir:</strong> ${formatTanggal(data.tanggal_lahir)}</p>
                                <p><strong>Jenis Kelamin:</strong> ${formatJenisKelamin(data.jenis_kelamin)}</p>
                                <p><strong>Alamat:</strong> ${data.alamat}</p>
                                <p><strong>Pekerjaan:</strong> ${data.pekerjaan}</p>
                                <p><strong>Wilayah Penerbit:</strong> ${data.wilayah}</p>
                                <p><strong>Berlaku Sampai:</strong> ${formatTanggal(data.berlaku_sampai)}</p>
                            </div>
                        </div>
                    `;
                } else {
                    resultElement.innerHTML = `<span class="text-danger">${result.message || 'Gagal membaca data OCR.'}</span>`;
                }
            })
            .catch(error => {
                resultElement.innerHTML = `<span class="text-danger">Gagal OCR</span>`;
                console.error("OCR error:", error);
            });
    }

    function handleKtpOcr(input) {
        const file = input.files[0];
        const resultElement = document.getElementById("ocr-result-ktp");

        if (!resultElement) {
            console.error("Elemen hasil OCR tidak ditemukan");
            return;
        }

        // tampilkan spinner/loading
        resultElement.innerHTML = "⏳ Memproses baca ktp...";

        const formData = new FormData();
        formData.append("ktp", file);

        fetch("{{ route('biodata.ocr.ktp') }}", {
                method: "POST",
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(result => {
                const resultElement = document.getElementById('ocr-result-ktp');
                if (!resultElement) return; // Jangan lanjut jika elemen tidak ada

                if (result.success) {
                    const data = result.data;

                    window.ktpOcrData = data;
                    if (window.simOcrData) {
                        compareSimKtpOcr(window.simOcrData, window.ktpOcrData);
                    }

                    resultElement.innerHTML = `
                    <div class="card mt-3">
                        <div class="card-header fw-bold">Hasil baca KTP :</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>NIK:</strong> ${data.result.nik?.value || '-'}</p>
                                    <p><strong>Nama Lengkap:</strong> ${data.result.nama?.value || '-'}</p>
                                    <p><strong>Tempat Lahir:</strong> ${data.result.tempatLahir?.value || '-'}</p>
                                    <p><strong>Tanggal Lahir:</strong> ${data.result.tanggalLahir?.value || '-'}</p>
                                    <p><strong>Jenis Kelamin:</strong> ${data.result.jenisKelamin?.value || '-'}</p>
                                    <p><strong>Golongan Darah:</strong> ${data.result.golonganDarah?.value || '-'}</p>
                                    <p><strong>Status Perkawinan:</strong> ${data.result.statusPerkawinan?.value || '-'}</p>
                                    <p><strong>Agama:</strong> ${data.result.agama?.value || '-'}</p>
                                    <p><strong>Pekerjaan:</strong> ${data.result.pekerjaan?.value || '-'}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Kewarganegaraan:</strong> ${data.result.kewarganegaraan?.value || '-'}</p>
                                    <p><strong>Alamat:</strong> ${data.result.alamat?.value || '-'}</p>
                                    <p><strong>RT/RW:</strong> ${data.result.rt?.value || '-'} / ${data.rw?.value || '-'}</p>
                                    <p><strong>Kelurahan/Desa:</strong> ${data.result.kelurahanDesa?.value || '-'}</p>
                                    <p><strong>Kecamatan:</strong> ${data.result.kecamatan?.value || '-'}</p>
                                    <p><strong>Kabupaten/Kota:</strong> ${data.result.kabupatenKota?.value || '-'}</p>
                                    <p><strong>Provinsi:</strong> ${data.result.provinsi?.value || '-'}</p>
                                    <p><strong>Diterbitkan di:</strong> ${data.result.tempatDiterbitkan?.value || '-'}</p>
                                    <p><strong>Tanggal Diterbitkan:</strong> ${data.result.tanggalDiterbitkan?.value || '-'}</p>
                                    <p><strong>Berlaku Hingga:</strong> ${data.result.berlakuHingga?.value || '-'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    `;
                } else {
                    resultElement.innerHTML = `<span class="text-danger">${result.message || 'Gagal membaca data KTP.'}</span>`;
                }
            })
            .catch(error => {
                resultElement.innerHTML = `<span class="text-danger">Gagal membaca data OCR</span>`;
            });
    }

    function compareSimKtpOcr(simData, ktpData) {
        const resultElement = document.getElementById("ocr-compare-result");
        if (!resultElement) return;

        const errors = [];

        /* =====================
           VALIDASI NAMA (OCR FRIENDLY)
        ===================== */
        const normalizeName = (name) =>
            name
            .toLowerCase()
            .replace(/[^a-z\s]/g, '') // hapus titik & simbol
            .trim()
            .split(/\s+/);

        const simParts = normalizeName(simData.nama || '');
        const ktpParts = normalizeName(ktpData.result.nama?.value || '');

        let namaSesuai = true;

        // Nama depan wajib sama
        if (!simParts[0] || !ktpParts[0] || simParts[0] !== ktpParts[0]) {
            namaSesuai = false;
        } else {
            // Setiap kata SIM harus cocok (full / inisial) dengan KTP
            for (let i = 0; i < simParts.length; i++) {
                const simWord = simParts[i];
                const ktpWord = ktpParts[i];

                if (!ktpWord || !ktpWord.startsWith(simWord)) {
                    namaSesuai = false;
                    break;
                }
            }
        }

        if (!namaSesuai) {
            errors.push('❌ Nama pada SIM tidak sesuai dengan nama pada KTP.');
        }

        /* =====================
           VALIDASI TANGGAL LAHIR
        ===================== */
        const simTanggal = formatTanggalIso(simData.tanggal_lahir);
        const ktpTanggal = formatTanggalIso(ktpData.result.tanggalLahir?.value);

        if (simTanggal !== ktpTanggal) {
            errors.push('❌ Tanggal lahir pada SIM tidak sesuai dengan KTP.');
        }

        /* =====================
           VALIDASI MASA BERLAKU SIM
        ===================== */
        const simBerlakuSampai = new Date(simData.berlaku_sampai);
        const today = new Date();

        if (simBerlakuSampai < today) {
            errors.push('❌ Tanggal berlaku SIM sudah kadaluarsa.');
        }

        /* =====================
           TAMPILKAN HASIL
        ===================== */
        if (errors.length > 0) {
            resultElement.innerHTML = `
            <div class="alert alert-warning" role="alert">
                <h5 class="mb-2">Hasil Validasi Data SIM dan KTP:</h5>
                <ul class="mb-0">
                    ${errors.map(e => `<li>${e}</li>`).join('')}
                </ul>
            </div>
        `;
        } else {
            resultElement.innerHTML = `
            <div class="alert alert-success" role="alert">
                ✅ Data SIM dan KTP sesuai.
            </div>
        `;
        }
    }


    // Fungsi bantu format tanggal jadi yyyy-mm-dd
    function formatTanggalIso(tanggal) {
        if (!tanggal) return '';
        const d = new Date(tanggal);
        if (isNaN(d.getTime())) return ''; // Cek invalid date
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function formatTanggal(tanggal) {
        const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        const [dd, mm, yyyy] = tanggal.split('-');
        return `${parseInt(dd)} ${bulan[parseInt(mm) - 1]} ${yyyy}`;
    }

    function formatJenisKelamin(jk) {
        if (!jk) return '';
        jk = jk.toUpperCase();
        return jk === 'PRIA' ? 'Pria' : jk === 'WANITA' ? 'Wanita' : jk;
    }
</script>

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
                            $('#kabupaten_id').append('<option value="" disabled selected>Pilih kabupaten</option>');
                            $.each(data, function(id, kabupaten) {
                                $('select[name="kabupaten"]').append('<option value="' + kabupaten.id + '">' + kabupaten.kabupaten + '</option>');
                            });
                        } else {
                            $('#kabupaten_id').empty().append('<option value="" disabled selected>Pilih kabupaten</option>');
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
                            $('#kecamatan_id').append('<option value="" disabled selected>Pilih kecamatan</option>');
                            $.each(data, function(id, kecamatan) {
                                $('select[name="kecamatan"]').append('<option value="' + kecamatan.id + '">' + kecamatan.kecamatan + '</option>');
                            })
                        } else {
                            $('#kecamatan_id').empty().append('<option value="" disabled selected>Pilih kecamatan</option>');
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
                        $('#kelurahan_id').empty();

                        // default kosong agar required bekerja
                        $('#kelurahan_id').append('<option value="" disabled selected>Pilih kelurahan/desa</option>');

                        if (data) {
                            $.each(data, function(id, kelurahan) {
                                $('#kelurahan_id')
                                    .append('<option value="' + kelurahan.id + '">' + kelurahan.kelurahan + '</option>');
                            });
                        }
                    }
                });
            } else {
                $('#kelurahan_id').empty().append('<option value="" disabled selected>Pilih kelurahan/desa</option>');
            }
        });
    });
</script>

<script>
    const tabs = Array.from(document.querySelectorAll('#formTabs .nav-link'));

    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('formWizard');
    let formSubmitting = false;

    // Tampilkan loading spinner saat form disubmit, dan cegah double-submit
    form.addEventListener('submit', function(e) {
        if (formSubmitting) {
            e.preventDefault();
            return;
        }

        formSubmitting = true;

        // Ganti teks tombol dengan spinner Bootstrap kecil
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Mengirim...';
        submitBtn.disabled = true;

        // Non-aktifkan navigasi agar user tidak berpindah langkah saat mengirim
        if (typeof nextBtn !== 'undefined' && nextBtn) nextBtn.disabled = true;
        if (typeof prevBtn !== 'undefined' && prevBtn) prevBtn.disabled = true;
    });

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
        if (!validateStep(currentStep)) return;
        // JIKA DARI STEP 4 (index 3) KE STEP 5
        // MAKA SIMPAN DULU VIA AJAX
        if (currentStep === 3) {
            saveStep1to4Ajax();
            return;
        }
        currentStep++;
        showStep(currentStep);
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

    const scrollBox = document.getElementById('termsBox');
    const checkBox = document.getElementById('checkBox1');

    scrollBox.addEventListener('scroll', function() {
        const {
            scrollTop,
            scrollHeight,
            clientHeight
        } = scrollBox;
        const isBottom = scrollTop + clientHeight >= scrollHeight - 5;

        if (isBottom) {
            checkBox.disabled = false;
        }
    });

    function checkCheckboxes() {
        const checkBox1 = document.getElementById('checkBox1');
        const submitBtn = document.getElementById('submitBtn');

        if (checkBox1.checked) {
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
        }
    }

    const masterCheckbox = document.getElementById('checkBox1');
    const childCheckboxes = document.querySelectorAll('.check-pasif');

    masterCheckbox.addEventListener('change', function() {
        childCheckboxes.forEach(cb => cb.checked = this.checked);
    });

    document.addEventListener('DOMContentLoaded', () => {
        // Cek awal
        checkCheckboxes();

        // Cek ulang setiap kali checkbox berubah
        document.getElementById('checkBox1').addEventListener('change', checkCheckboxes);
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

    function saveStep1to4Ajax() {

        nextBtn.disabled = true;
        nextBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';

        let formData = new FormData(document.getElementById('formWizard'));

        fetch("{{ route('biodata.storeStep1to4') }}", {
                method: "POST",
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.status) {
                    currentStep++;
                    showStep(currentStep);
                } else {
                    alert(res.message || 'Gagal menyimpan data');
                }
            })
            .catch(() => {
                alert('Terjadi kesalahan server');
            })
            .finally(() => {
                nextBtn.disabled = false;
                nextBtn.innerHTML = 'Selanjutnya';
            });
    }
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